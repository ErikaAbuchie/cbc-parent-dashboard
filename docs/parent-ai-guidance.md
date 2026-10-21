# Parent AI guidance: two prompt templates

## Explain results

The first action explains EE, ME, AE and BE in everyday language, then summarises the recorded subject results. The page always displays a fixed rating guide for all four levels, including zero counts. Means shown elsewhere are supplementary rubric averages, not official overall grades or test percentages.

The summary prompt asks for short sentences, supported strengths and support needs, and no home activities. The model receives only grade level, counts by subject and up to five example topics with AE/BE. Names, admission numbers, contact details, teacher remarks and access credentials are excluded.

## Home tips

Parents select a subject and strand from their report and request home tips. The second prompt receives only that selected strand's assessed sub-strands and recorded levels. It requests two or three concrete steps for a short, low-cost activity and a simple check of understanding, prioritising support needs or offering an extension for ME/EE. No guaranteed grade improvement is claimed.

The backend validates both IDs together against the authorised learner, teacher, term and report scope. Unassessed and mismatched selections are rejected before model inference. User-supplied prompts, ratings and learner IDs are ignored. Changing the selection clears old tips.

## Integration and reliability

Both use the existing `POST /api/parent/explanation`, with mode `summary` (default) or `home_tips`. Home tips require `subject_id` and `strand_id`. Ollama configuration is unchanged. No migration or dependency installation is needed.

Successful output is cached for ten minutes, separately by task, selection, report, grade, current ratings and model configuration. Both actions share the existing per-access rate limit. Access is checked again after inference. Provider timeouts, disabled AI and malformed output return clearly labelled standard guidance; the home fallback is general topic-specific practice advice rather than a model-generated specialised lesson. The original report remains readable.

The local model is allowed 120 seconds, with a 130-second inference lock, 135-second Nuxt upstream timeout and 145-second browser timeout. The page states up to two minutes. Fallback responses include only a fixed reason code (timeout, connection, disabled, provider error, incomplete or invalid response); raw provider errors and content are not exposed.

### Checking Ollama locally

In PowerShell, `ollama list` should include the configured model, normally `phi3:mini`. `Invoke-RestMethod http://127.0.0.1:11434/api/tags` checks the service. If the service is stopped, start the Ollama application or run `ollama serve` in a separate terminal; if the model is missing, run `ollama pull phi3:mini`. The existing Laravel settings are OLLAMA_ENABLED, OLLAMA_URL and OLLAMA_MODEL. After changing backend settings, run `php artisan config:clear` and restart the development server. Restart Nuxt to ensure proxy changes are loaded.

21 October 2026: a synthetic request reproduced the old 45-second timeout. After increasing the limit, the exact summary service returned validated model output in 51.12 seconds. No learner details were used in that diagnostic request.

The home-tips service also returned validated model output in 44.48 seconds. All nine AI feature tests passed with 71 assertions after the timeout/diagnostic changes. Response times vary with model load and hardware; the fallback remains available.

## Verification

21 October 2026: backend suite passed with 61 tests and 622 assertions. AI requests in these tests use fake provider responses, so they verify integration and access controls rather than the quality of live model output.

Automated tests cover prompt isolation, privacy of model inputs, selection validation, cache separation, refresh after rating changes, revoked sessions, provider failures and fallback labels. The frontend production build verifies compilation. Manual parent-session checks: request a summary; select Mathematics and Numbers; request tips; change subject and verify old tips disappear; repeat on mobile. AI wording and educational usefulness still require human evaluation.
