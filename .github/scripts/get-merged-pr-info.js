module.exports = async ({ github, context, core }) => {
    const { data: prs } = await github.rest.repos.listPullRequestsAssociatedWithCommit({
        owner: context.repo.owner,
        repo: context.repo.repo,
        commit_sha: context.sha,
    });

    const mergedPr = prs.find((pr) => pr.merged_at);
    if (!mergedPr) {
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

    const fallbackEmail = `${mergedPr.user.id}+${mergedPr.user.login}@users.noreply.github.com`
    try {
        const { data: user } = await github.rest.users.getByUsername({
            username: mergedPr.user.login,
        });

        const email = user.email || fallbackEmail;
        core.setOutput('author_email', email);
    } catch (e) {
        core.setOutput('author_email', fallbackEmail);
    }
};
