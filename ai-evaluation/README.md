# English / Kiswahili baseline

This is a small capability pilot before fine-tuning, not a complete evaluation or training dataset. It uses four fictional ratings, two tasks and two languages (four requests). It never queries the learner database. The application remains unchanged.

The production AI service currently requests English and supplies counts for its summary. This evaluation instead supplies subjects and strands and requests either English or Kiswahili, matching the proposed bilingual feature. Consequently, this is a baseline for the **proposed task**, not an end-to-end test of the existing dashboard.

## Run from the project root in PowerShell

1. Open Ollama and wait for it to start.
2. Run `ollama list` and note the exact installed model name. Do not download a different model just for this test.
3. Check the evaluation without running the model:

```powershell
php backend/scripts/bilingual-baseline.php --dry-run --output=output/ai-evaluation/input-check.json
```

4. Run the real test. Replace MODEL_NAME with the exact installed name:

```powershell
php backend/scripts/bilingual-baseline.php --model=MODEL_NAME --output=output/ai-evaluation/before-finetuning.json
```

Each request allows 120 seconds; all four can take several minutes. The output is saved after each case. A request failure is not a language-quality result. No standard fallback is substituted for a model response.

5. Open the saved JSON in VS Code. For each result read `input`, `answer` and `raw_response`. Record the review in a copy of `review.csv`. Have a fluent Kiswahili speaker review the Kiswahili answers. `needs_human_review` only means the request completed with the required basic structure; it does not establish accuracy or language quality.

## Review rubric

Score each category 0 (fails), 1 (partly meets), or 2 (meets):

- Language: requested language, natural wording, acceptable curriculum terminology.
- Factual accuracy: correct rating meanings and subject/strand associations; no changed ratings.
- Clarity: short, everyday explanations suitable for a parent.
- Task relevance: summaries explain supplied results; tips address Mathematics / Numbers and prioritise Fractions (AE).
- Constraints: no invented marks, causes or improvement claims; tips safe and feasible, summary contains no activity.

An invented result or unsafe activity must be recorded as a critical failure regardless of total score. Review tips for a short activity and a way to check understanding. Do not average away critical failures.

Expected facts: Fractions AE; Whole Numbers ME; English Reading Comprehension EE; English Punctuation BE. These are fictional test labels, not a claim about official Grade 5 curriculum wording.

## Later comparison

Keep this prompt, inputs, schema and generation settings unchanged when running the fine-tuned model; save to a different file. Do not train on these cases or their translations/paraphrases. Expand the held-out evaluation to cover all ratings, subjects, missing data, all-strong results and varied home-tip requests before drawing conclusions. Compare English as well as Kiswahili. These four cases alone cannot establish general improvement.
