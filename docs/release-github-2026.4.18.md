# Release 2026.4.18

## Summary
This release focuses on safer billing behavior, messaging improvements, customer order template refactoring, and branding asset refresh.

## Highlights
- Introduce safe `disable_invoice_attribute` mode in billing flow.
- Add admin toggle to control `disable_invoice_attribute` behavior.
- Add updater SQL entry for `disable_invoice_attribute` rollout.
- Improve messaging flow and update EN/ID translation strings.
- Refactor customer order and payment gateway templates.
- Refresh branding assets (logo and favicon).

## Commit Breakdown
### e46f237 — feat: add safe disable_invoice_attribute mode
- Add safe mode to disable invoice attribute usage in core billing flow.
- Update recharge, order, plan, and reminder behavior to use safe fallback when enabled.
- Add updater integration and admin UI setting for controlled rollout.

### 6326c4d — feat: improve messaging flow and language updates
- Improve message sending flow stability.
- Update bulk messaging UI behavior.
- Refresh English and Indonesian language strings related to messaging.
- Include logout flow adjustment tied to the updated messaging/session behavior.

### 272f16f — refactor: update customer order and gateway templates
- Refactor customer order templates for cleaner structure.
- Align gateway selection templates with the updated order flow.
- Keep backup template aligned with main template changes.

### d716a7b — chore: refresh branding assets and header logo references
- Refresh favicon and logo assets.
- Update admin header logo references to match new branding files.

## Post-Deployment Checklist
1. Verify `disable_invoice_attribute` value in app settings.
2. Test end-to-end order, recharge, reminder, and message flows.
3. Hard refresh browser/client cache to load new branding assets.
