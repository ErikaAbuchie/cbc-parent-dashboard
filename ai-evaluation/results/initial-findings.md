# Initial bilingual capability pilot

Model: `phi3:mini`. Four fictional cases, using the standalone evaluation prompt. No fine-tuning performed.

| Case | Time (seconds) | Observation |
|---|---:|---|
| summary-en | 91.36 | Completed, but changed Fractions AE to meeting expectations and supplied an activity when none was requested. Factual and task-following failures. |
| summary-sw | 120.76 | Request timed out. No language-quality judgement is possible. |
| home_tips-en | 21.56 | Completed with valid basic structure, but described Fractions AE as better than expected. Factual failure; not a passing answer. |
| home_tips-sw | 101.1 | Repeated a phrase until the output-token limit; incomplete JSON and no usable activity. |

These are preliminary observations, not a statistical evaluation. None of the four cases provides an acceptable answer for deployment. A fluent Kiswahili reviewer is still needed for substantive language scoring; no scores have been invented.

The evidence supports improving factual grounding and task-following as well as Kiswahili generation. It does not establish that fine-tuning this particular model will succeed. Audit suitable bilingual data and assess base-model language capability before committing training resources.

Raw inputs, prompt, schema, model options and outputs are preserved in `phi3-baseline.json`. Retain these cases outside training and reuse identical evaluation settings for the later comparison.
