# PR Metrics - GitHub Action

This action gathers pull-request and contribution metrics for a GitHub repository. 

It fetches repository activity and computes:
- **PR size** (commits & file changes)
- **PR lead time** (open → merged)
- **PR review lead time** (first review lead time)
- **PR total comments & review cycles**
- **Developer daily commits**
- **Developer daily gross contributions** (lines added/removed)

It is implemented as a composite action gathering data via the GitHub API and then created a CSV file with the results. The
output file (exposed as an action output) can be used for further analysis or feeding dashboards.

## Why use this?

Track team throughput and review health:
- Spot PRs at risk (too many changes, big PR lead time)
- See daily contribution trends per developer or team
- Build dashboards off a repeatable export

## Team & identity normalization

You will need to provide a YAML file (default `.github/pr-metrics-mappings.yml`) to unify identities (e.g., different emails) 
and map developers to teams:

Here is an example `pr-metrics-mappings.yml` file:
```json
{
   "user_index": {
     "12345678": "Vassilis Poursalidis",
     ...
  },
  "developer_index": {
    "Vassilis Poursalidis#poursalidis@example.com": "Vassilis Poursalidis",
    "Vassilis Poursalidis#12345678+poursalidis@users.noreply.github.com": "Vassilis Poursalidis",
    ...
  },
  "team_index": {
    "Vassilis Poursalidis": "Team Courses",
    ...
  }
}
```

### User index

The `user_index` maps GitHub user IDs to normalized names. You can find the user ID in the URL of a user's profile photo.

### Developer index

The `developer_index` maps combinations of GitHub usernames and email addresses to normalized names, as found in the commit authors.

### Team index

The `team_index` maps normalized developer names to team names.

## Quick start

In the repo you want to analyze, create a workflow file (ie `.github/workflows/pr-metrics.yml`) with the following contents:

```yaml
name: PR Metrics (Scheduled)

on:
  schedule:
    # Weekdays at 07:00 UTC
    - cron: "0 7 * * 1-5"
  workflow_dispatch: {}

permissions:
  contents: read
  pull-requests: read
  issues: read

jobs:
  collect:
    runs-on: ubuntu-latest
    steps:
      - name: Collect PR metrics
        id: metrics
        uses: epignosis/pr-metrics
        with:
          # Optional overrides — defaults are shown in the Inputs section
          sprint-start-date: "2025-01-06"
          github-ignore-labels: "release"
          github-ignore-users: "41898282,49699333,163396788,39604003"
          github-ignore-commit-messages: "Merge branch,Merge remote-tracking branch,Merge pull request,Merging,Auto PR: Sync"

      # Example: Upload metrics to S3 for further processing
      - name: Upload metrics to S3
        uses: jakejarvis/s3-sync-action@v0.5.1
        with:
          args: --acl public-read
          bucket: ${{ secrets.AWS_S3_BUCKET }}
          aws-access-key-id: ${{ secrets.AWS_ACCESS_KEY_ID }}
          aws-secret-access-key: ${{ secrets.AWS_SECRET_ACCESS_KEY }}
          source_dir: ${{ steps.metrics.outputs.captured-metrics }}
          dest_dir: pr-metrics/
```

## Inputs and outputs

The action support multiple configuration options via inputs. All inputs have sensible defaults, so you only need to override what is relevant to your use case.

The most common inputs to override are:
- `sprint-start-date`: The start date of the sprint in `YYYY-MM-DD`
- `github-ignore-labels`: A comma-separated list of labels to ignore
- `github-ignore-users`: A comma-separated list of user IDs to ignore
- `github-ignore-commit-messages`: A comma-separated list of commit message patterns to ignore

### Inputs

| Name | Description | Default |
|---|---|---|
| `github-mappings-path`          | The file path for normalizing developers and mapping to teams. | `.github/pr-metrics-mappings.yml`                                                    |
| `metrics-contributions`         | Whether to collect contribution metrics.                       | true                                                                                 |
| `github-token`                  | GitHub access token (or PAT) for accessing the repo.           | `${{ github.token }}`                                                                |
| `github-repository`             | The full name of the repository in which to gather metrics.    | `${{ github.repository }}`                                                           |
| `httpclient`                    | The HTTP client to use (Guzzle).                               | Guzzle                                                                               |
| `guzzle-timeout`                | The timeout for HTTP requests in seconds.                      | 5                                                                                    |
| `guzzle-retry-enabled`          | Whether to enable retry for HTTP requests.                     | true                                                                                 |
| `guzzle-retry-max-attempts`     | The maximum number of retry attempts for HTTP requests.        | 5                                                                                    |
| `guzzle-retry-on-timeout`       | Whether to retry on timeout errors.                            | true                                                                                 |
| `guzzle-retry-on-status`        | A comma-separated list of HTTP status codes to retry on.       | 500,502,503,504                                                                      |
| `guzzle-cache-enabled`          | Whether to enable caching for HTTP requests.                   | true                                                                                 |
| `guzzle-cache-ttl`              | The time-to-live for the cache in seconds.                     | 14400                                                                                |
| `guzzle-cache-path`             | The path to store the cache files.                             | `tmp/cache`                                                                          |
| `sprint-start-date`             | The start date of the sprint in `YYYY-MM-DD` format.           | 2025-01-06                                                                           |
| `github-ignore-labels`          | A comma-separated list of labels to ignore.                    | release                                                                              |
| `github-ignore-users`           | A comma-separated list of user IDs to ignore (e.g., bots).     | 41898282,49699333,163396788,39604003                                                 |
| `github-ignore-commit-messages` | A comma-separated list of commit message patterns to ignore.   | Merge branch,Merge remote-tracking branch,Merge pull request,Merging,Auto PR: Sync   |
| `php-version`                   | PHP version to install                                         | 8.3                                                                                  |

### Outputs

| Name | Description |
|---|---|
| `captured-metrics` | The path to the directory containing the captured metrics file. |

### Example output

```csv
"Repository","Sprint","Pull Request","Creator","Team","State","Created date","Closed date","First review date","Merged?","# of comments","# of reviews","# of review cycles","# of commits","# of changes","Developer","Developer team","Commit date","# of developer commits","# of developer changes"
"epignosis/pr-metrics","2025 Sprint 1",1,"dev1","Team A","closed","2025-01-10","2025-01-11","2025-01-10","Yes","5","2","1","3","100",,"",,
"epignosis/pr-metrics","2025 Sprint 1",1,"dev1","Team A","closed","2025-01-10","2025-01-11","2025-01-10","Yes","5","2","1","3","100","dev1","Team A","2025-01-10","1","50"
"epignosis/pr-metrics","2025 Sprint 1",1,"dev1","Team A","closed","2025-01-10","2025-01-11","2025-01-10","Yes","5","2","1","3","100","dev2","Team B","2025-01-11","2","50"
```

## License

This project is licensed under the Apache License 2.0 - see the [LICENSE](LICENSE) file for details.
