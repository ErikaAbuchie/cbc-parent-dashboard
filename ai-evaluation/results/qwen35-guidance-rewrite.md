# Qwen3.5-4B: rewriting verified bilingual guidance

This follow-up used the same 26 held-out cases as the generation pilot: 13 English and 13 Kiswahili, with ten home tips and three report summaries per language. The base model received the written guidance as part of each input and was asked to simplify it without changing the ratings or practical advice. No adapter was loaded. The test measures fidelity when the answer is supplied, rather than independent generation.

| Check | English | Kiswahili | Total |
| --- | ---: | ---: | ---: |
| Valid response format | 13/13 | 11/13 | 24/26 |
| Preserved rating meaning and topic attribution | 13/13 | 12/13 | 25/26 |
| Preserved the home activity wording | 10/10 | 10/10 | 20/20 |
| Returned the entire answer unchanged after apostrophe normalization | 10/13 | 7/13 | 17/26 |
| Valid and faithful to the source, including practical advice | 11/13 | 10/13 | 21/26 |

Format required a JSON object with exactly two strings, `summary` and `activity`, with an empty activity for summaries. The rating check assessed meaning and attribution separately from format. Faithful answers also had to retain the source advice without introducing misleading or unsolicited content. The case review is in the local artifact `output/ai-evaluation/guidance-pilot/guidance-rewrite-review.csv`.

Two English report summaries removed advice about practicing an example together. One Kiswahili home tip kept the original activity field but mislabeled Soil Conservation as “uchumi wa udongo” in its summary. Two Kiswahili summaries added activities that were not requested; one was also truncated. There was no independent fluent Kiswahili review, and this test did not establish a readability gain.

The earlier open-generation runs returned usable response formatting in 0/26 base answers and 2/26 fine-tuned answers. The rewrite returned valid formatting in 24/26, with the key difference that the answer was already in the prompt. Most outputs repeated the source, and five still failed the fidelity or format check. Keep the written guidance in the parent portal and report the rewrite as a separate evaluation.

Run: Qwen/Qwen3.5-4B at revision `851bf6e806efd8d0a36b00ddf55e13ccb7b8cd0a`, Transformers 5.22.2, NF4 with FP16 compute, greedy decoding, thinking disabled, maximum 320 generated tokens. Input SHA-256: `46b08c4671cb03525b33b0f44f049e88669f896911fcc94eb9f6ffd4f005d35c`. All cases, references, and downloaded response records were checked against the test files.

## Fine-tuned adapter comparison

The adapter run used identical test inputs, references, model revision, quantization and generation settings. The adapter was included in the results ZIP and its weights were saved locally. The test does not establish whether the runtime reused the original checkpoint or repeated the same training recipe.

| Check | Base English | Tuned English | Base Kiswahili | Tuned Kiswahili |
| --- | ---: | ---: | ---: | ---: |
| Valid and faithful | 11/13 | 12/13 | 10/13 | 10/13 |
| Valid response format | 13/13 | 13/13 | 11/13 | 11/13 |
| Preserved rating meaning and topic attribution | 13/13 | 13/13 | 12/13 | 12/13 |
| Preserved home activity wording | 10/10 | 10/10 | 10/10 | 10/10 |
| Unchanged wording after apostrophe normalization | 10/13 | 10/13 | 7/13 | 9/13 |

Two cases improved and one regressed, for a net increase from 21/26 to 22/26 faithful, valid answers. One English summary retained the advice that the base model omitted. The Kiswahili Soil Conservation case preserved the original topic, while a different Kiswahili summary misrepresented Heat Transfer as heat production and placed Democracy in Society under economics. Both runs failed the same two Kiswahili summary format checks. With 19/26 answers repeating the source after fine-tuning and no independent fluent Kiswahili review, the test does not establish a readability gain.

The case review is available locally in `output/ai-evaluation/guidance-pilot/guidance-rewrite-tuned-review.csv`. Adapter weights SHA-256 is `95e8ddff702ecbbd92b62df315fa47a14dc415578e95d8b48c120b7dda892390`.
