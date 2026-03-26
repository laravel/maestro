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
        core.setOutput('co_authors', '');

        return;
    }

    core.setOutput('title', mergedPr.title);
    core.setOutput('number', mergedPr.number);
    core.setOutput('author', mergedPr.user.login);
    core.setOutput('author_url', mergedPr.user.html_url);
    core.setOutput('url', mergedPr.html_url);
    core.setOutput('found', 'true');

    const resolveEmail = async (username, userId) => {
        const fallback = `${userId}+${username}@users.noreply.github.com`;
        try {
            const { data: user } = await github.rest.users.getByUsername({ username });
            return user.email || fallback;
        } catch (e) {
            core.warning(`Failed to fetch user email for ${username}: ${e.message}`);
            return fallback;
        }
    };

    const authorEmail = await resolveEmail(mergedPr.user.login, mergedPr.user.id);
    core.setOutput('author_email', authorEmail);

    // Collect co-authors from PR commits and commit message trailers
    const coAuthors = new Map();

    try {
        const { data: commits } = await github.rest.pulls.listCommits({
            owner: context.repo.owner,
            repo: context.repo.repo,
            pull_number: mergedPr.number,
        });

        for (const commit of commits) {
            // Add commit authors that differ from the PR author
            const commitAuthor = commit.author;
            if (commitAuthor && commitAuthor.login && commitAuthor.login !== mergedPr.user.login) {
                if (!coAuthors.has(commitAuthor.login)) {
                    coAuthors.set(commitAuthor.login, { id: commitAuthor.id, login: commitAuthor.login });
                }
            }

            // Parse "Co-authored-by" trailers from commit messages
            const message = commit.commit.message || '';
            const coAuthorPattern = /^Co-authored-by:\s+(.+?)\s+<(.+?)>\s*$/gim;
            let match;
            while ((match = coAuthorPattern.exec(message)) !== null) {
                const [, name, email] = match;
                // Skip the PR author and bot accounts
                if (!email.includes('[bot]') && !coAuthors.has(email)) {
                    coAuthors.set(email, { name, email });
                }
            }
        }
    } catch (e) {
        core.warning(`Failed to fetch PR commits: ${e.message}`);
    }

    // Resolve co-author emails and build trailer lines
    const coAuthorLines = [];
    for (const [key, value] of coAuthors) {
        if (value.login) {
            const email = await resolveEmail(value.login, value.id);
            coAuthorLines.push(`Co-authored-by: ${value.login} <${email}>`);
        } else {
            coAuthorLines.push(`Co-authored-by: ${value.name} <${value.email}>`);
        }
    }

    core.setOutput('co_authors', coAuthorLines.join('\n'));
};
