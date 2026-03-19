module.exports = async ({ github, context, core }) => {
    const maxRetries = 5;
    const retryDelay = 2000; // 2 seconds

    const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

    const findMergedPr = async () => {
        try {
            const { data: prs } = await github.rest.repos.listPullRequestsAssociatedWithCommit({
                owner: context.repo.owner,
                repo: context.repo.repo,
                commit_sha: context.sha,
            });

            return prs.find((pr) => pr.merged_at) || null;
        } catch (error) {
            core.warning(`API request failed: ${error.message}`);

            return null;
        }
    };

    let mergedPr = null;

    for (let attempt = 1; attempt <= maxRetries; attempt++) {
        core.info(`Attempt ${attempt}/${maxRetries}: Looking for merged PR associated with commit ${context.sha}`);

        mergedPr = await findMergedPr();

        if (mergedPr) {
            core.info(`Found merged PR #${mergedPr.number}: ${mergedPr.title}`);
            break;
        }

        if (attempt < maxRetries) {
            core.info(`No merged PR found yet, retrying in ${retryDelay / 1000} seconds...`);
            await sleep(retryDelay);
        }
    }

    if (!mergedPr) {
        core.warning(`No merged PR found after ${maxRetries} attempts for commit ${context.sha}`);
        core.setOutput('title', 'Update from Maestro');
        core.setOutput('found', 'false');
        core.setOutput('author', '');
        core.setOutput('author_email', '');

        return;
    }

    core.setOutput('title', mergedPr.title);
    core.setOutput('number', mergedPr.number);
    core.setOutput('author', mergedPr.user.login);
    core.setOutput('author_url', mergedPr.user.html_url);
    core.setOutput('url', mergedPr.html_url);
    core.setOutput('found', 'true');

    const fallbackEmail = `${mergedPr.user.id}+${mergedPr.user.login}@users.noreply.github.com`;
    try {
        const { data: user } = await github.rest.users.getByUsername({
            username: mergedPr.user.login,
        });

        const email = user.email || fallbackEmail;
        core.setOutput('author_email', email);
    } catch (e) {
        core.warning(`Failed to fetch user email for ${mergedPr.user.login}: ${e.message}`);
        core.setOutput('author_email', fallbackEmail);
    }
};
