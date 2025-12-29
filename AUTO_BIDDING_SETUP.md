# Auto-Bidding System Setup Guide

## Overview

This system implements a complete auto-bidding solution for Freelancer.com with:
- Queue-based background processing
- Rate limiting to respect API limits
- Error handling and retry logic
- Project search and bid submission

## Components

### 1. Queue Job: `ProcessAutoBidding`
- Located: `app/Jobs/ProcessAutoBidding.php`
- Processes auto-bidding for a single user
- Searches for matching projects
- Submits bids based on user settings
- Records results in database

### 2. Command: `ProcessAutoBiddingCommand`
- Located: `app/Console/Commands/ProcessAutoBiddingCommand.php`
- Dispatches jobs for all active users
- Usage: `php artisan bidding:process`
- Options:
  - `--user-id=123`: Process for specific user
  - `--limit=10`: Limit number of users to process

### 3. Services

#### `FreelancerApiService`
- Handles all Freelancer API interactions
- Includes retry logic with exponential backoff
- Rate limiting integration
- Error handling for various API errors

#### `RateLimiterService`
- Tracks API requests per minute/hour
- Prevents exceeding Freelancer API limits (60/min, 1000/hour)
- Implements wait logic when limits are reached

## Setup Instructions

### 1. Configure Queue

The system uses Laravel's database queue driver by default. Make sure your `.env` has:

```env
QUEUE_CONNECTION=database
```

### 2. Run Migrations

```bash
php artisan migrate
```

This creates the `jobs` and `failed_jobs` tables needed for queue processing.

### 3. Start Queue Worker

Run the queue worker to process jobs:

```bash
php artisan queue:work --queue=bidding
```

Or use the dev script which includes queue worker:

```bash
npm run dev
```

### 4. Schedule Auto-Bidding (Optional)

The system is configured to run every 5 minutes automatically. Make sure your cron is set up:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

Or manually run the command:

```bash
php artisan bidding:process
```

## How It Works

### 1. User Enables Auto-Bidding

When a user clicks "Start Auto-Bidding":
- System checks for active subscription
- Verifies remaining bids
- Enables auto-bidding flag
- Dispatches immediate job

### 2. Job Processing

For each user with active auto-bidding:

1. **Validation Checks:**
   - Active subscription exists
   - Remaining bids > 0
   - Access token valid and not expired
   - Within configured bidding times

2. **Project Search:**
   - Uses user's bidding settings (countries, technologies, categories, budget)
   - Searches Freelancer API for matching projects
   - Filters results based on criteria

3. **Bid Submission:**
   - Calculates bid amount (85% of project budget by default)
   - Respects user's max bid amount limit
   - Uses user's cover letter template
   - Submits bid via Freelancer API

4. **Result Recording:**
   - Records successful bids in `bidding_jobs` table
   - Records failed bids with error messages
   - Increments bids used in subscription

### 3. Rate Limiting

- Tracks requests per access token
- Limits: 60 requests/minute, 1000 requests/hour
- Automatically waits when limits are reached
- Uses exponential backoff for retries

### 4. Error Handling

- Retries failed requests up to 3 times
- Handles rate limit errors (429)
- Handles authentication errors (401)
- Handles server errors (5xx) with retry
- Logs all errors for debugging

## Monitoring

### Check Queue Status

```bash
php artisan queue:monitor
```

### View Failed Jobs

```bash
php artisan queue:failed
```

### Retry Failed Jobs

```bash
php artisan queue:retry all
```

### View Logs

Logs are written to:
- `storage/logs/laravel.log` - General application logs
- `storage/logs/bidding.log` - Scheduled command output

## Configuration

### Rate Limits

Edit `app/Services/RateLimiterService.php` to adjust:
- Requests per minute (default: 60)
- Requests per hour (default: 1000)

### Job Retry Settings

Edit `app/Jobs/ProcessAutoBidding.php`:
- `$tries` - Number of retry attempts (default: 3)
- `$backoff` - Seconds between retries (default: 60)

### Scheduling Frequency

Edit `routes/console.php` to change how often auto-bidding runs:
- Currently: Every 5 minutes
- Change `everyFiveMinutes()` to your preferred interval

## Testing

### Test for Specific User

```bash
php artisan bidding:process --user-id=1
```

### Test with Limit

```bash
php artisan bidding:process --limit=5
```

### Manual Job Dispatch

You can also dispatch jobs manually in code:

```php
use App\Jobs\ProcessAutoBidding;

ProcessAutoBidding::dispatch($userId)->onQueue('bidding');
```

## Troubleshooting

### Jobs Not Processing

1. Check queue worker is running: `php artisan queue:work`
2. Check queue connection in `.env`: `QUEUE_CONNECTION=database`
3. Check for failed jobs: `php artisan queue:failed`

### Rate Limit Errors

- System automatically handles rate limits
- If persistent, reduce `--limit` in scheduled command
- Check `storage/logs/laravel.log` for rate limit warnings

### Token Expired Errors

- Users need to re-authenticate via Freelancer OAuth
- System will skip users with expired tokens
- Implement token refresh logic (TODO)

### No Projects Found

- Check user's bidding settings are configured
- Verify countries, technologies, categories are valid
- Check Freelancer API is responding correctly

## Security Notes

- Access tokens are stored encrypted in database
- Rate limiting prevents abuse
- Jobs are processed in background (non-blocking)
- Failed jobs are logged for review

## Next Steps

1. Implement token refresh logic for expired tokens
2. Add email notifications for bid status
3. Add dashboard for monitoring bid success rates
4. Implement bid strategy customization (percentage of budget)
5. Add project filtering by client rating/history

