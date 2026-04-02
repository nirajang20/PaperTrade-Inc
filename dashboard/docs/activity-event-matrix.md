# Activity Event Matrix

This matrix defines the exact timeline output for each feature action so wording and status remain consistent.

| Feature | User Action | Event Type | Timeline Title | Status | Note | Module | Metadata |
|---|---|---|---|---|---|---|---|
| Trading > Buy | Buy order submitted | `trade.buy` | `Bought {SYMBOL}` | `Success` / `Failed` | `Buy order recorded.` / `Buy order failed.` | `Trading` | `symbol`, `order_mode` |
| Trading > Sell | Sell order submitted | `trade.sell` | `Sold {SYMBOL}` | `Success` / `Failed` | `Sell order recorded.` / `Sell order failed.` | `Trading` | `symbol`, `order_mode` |
| Portfolio > Watchlist | Add symbol | `watchlist.add` | `Added {SYMBOL} to watchlist` | `Success` / `Failed` | `Watchlist item added.` / `Watchlist update failed.` | `Portfolio` | `symbol` |
| Portfolio > Watchlist | Remove symbol | `watchlist.remove` | `Removed {SYMBOL} from watchlist` | `Success` / `Failed` | `Watchlist item removed.` / `Watchlist update failed.` | `Portfolio` | `symbol`, `watchlist_id` |
| Portfolio > Export | Export CSV | `portfolio.export_csv` | `Exported portfolio CSV` | `Success` / `Failed` | `Portfolio export completed.` / `Portfolio export failed.` | `Portfolio` | `symbol`, `period`, `interval` |
| My Account > Profile | Change name | `account.profile_name_updated` | `Updated profile name` | `Success` | `Profile details were updated.` | `Account` | none |
| My Account > Profile | Change email | `account.email_updated` | `Updated email` | `Success` | `Profile details were updated.` | `Account` | none |
| My Account > Profile | Change password | `account.password_changed` | `Changed password` | `Success` | `Security credentials were updated.` | `Account` | none |
| Auth > Login | Failed password | `auth.sign_in_failed` | `Failed sign-in attempt` | `Failed` | `A sign-in attempt was rejected.` | `Account` | `email` |
| Auth > Login | New device sign-in | `auth.new_device_sign_in` | `Signed in from new device` | `Warning` | `A new-device sign-in was detected.` | `Account` | `device` |
| Activity Engine | Repeated failed sign-ins (>=3 in 15 min) | `security.suspicious_signin` | `Suspicious sign-in activity` | `Warning` | `Multiple failed sign-in attempts were detected in a short period.` | `Account` | `failed_attempts`, `window_minutes` |
| Dashboard | Open dashboard | `dashboard.opened` | `Viewed dashboard` | `Success` | `Dashboard was opened.` | `Dashboard` | none |
| Trading > Reminder | Create reminder | `trading.reminder_created` | `Created price reminder` | `Success` / `Failed` | `Reminder saved.` / `Reminder save failed.` | `Trading` | `symbol`, `target_price`, `condition` |
| Trading > Reminder | Delete reminder | `trading.reminder_deleted` | `Deleted price reminder` | `Success` / `Failed` | `Reminder removed.` / `Reminder remove failed.` | `Trading` | `reminder_id`, `symbol` |
| Search Stocks | Search symbol | `search.symbol_queried` | `Searched stock symbol` | `Success` | `Market symbol search completed.` | `Dashboard` | `query`, `results_count` |
| Trading > My Transactions | View transaction details | `trading.transaction_viewed` | `Viewed transaction details` | `Success` | `Transaction details were opened.` | `Trading` | `transaction_id` |
| Portfolio > My Holdings | Open holdings page | `portfolio.holdings_viewed` | `Viewed holdings` | `Success` | `Holdings snapshot was viewed.` | `Portfolio` | none |
| Portfolio > Performance | Open performance page | `portfolio.performance_viewed` | `Viewed performance` | `Success` | `Performance analytics were viewed.` | `Portfolio` | `range` |
| Portfolio > Stock Screener | Run screener | `portfolio.screener_run` | `Ran stock screener` | `Success` | `Screener results were generated.` | `Portfolio` | `filters`, `results_count` |
| My Account > Notifications | Update notification settings | `account.notifications_updated` | `Updated notification settings` | `Success` / `Failed` | `Notification settings were updated.` / `Notification settings update failed.` | `Account` | `channels`, `frequency` |
| Activities | Open activity page | `activity.timeline_viewed` | `Viewed activity timeline` | `Success` | `Activity timeline was opened.` | `Account` | none |

## Implementation Status

- Implemented in code now: `trade.buy`, `trade.sell`, `watchlist.add`, `watchlist.remove`, `portfolio.export_csv`, `account.profile_name_updated`, `account.email_updated`, `account.password_changed`, `auth.sign_in_failed`, `auth.new_device_sign_in`, `security.suspicious_signin`.
- Planned next: dashboard, reminder, search, transactions, holdings, performance, screener, notifications, timeline-view events above.
