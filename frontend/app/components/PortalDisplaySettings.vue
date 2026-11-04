<script setup lang="ts">
const { size } = usePortalTextSize()
const options = [
  { value: 'standard', label: 'Standard', description: 'Keep the current text size.' },
  { value: 'large', label: 'Large', description: 'Make text a little bigger.' },
  { value: 'extra-large', label: 'Extra large', description: 'Use the largest text size.' },
]
</script>

<template>
  <section class="display-settings" aria-labelledby="display-settings-title">
    <span class="eyebrow">MAKE YOURSELF COMFORTABLE</span>
    <h1 id="display-settings-title">Display settings</h1>
    <p>Choose the text size that feels easiest to read. Your choice applies throughout this portal.</p>
    <fieldset><legend>Text size</legend><div class="text-size-options">
      <label v-for="option in options" :key="option.value" :class="{ selected: size === option.value }">
        <input v-model="size" type="radio" name="portal-text-size" :value="option.value">
        <span><strong>{{ option.label }}</strong><small>{{ option.description }}</small></span>
      </label>
    </div></fieldset>
    <div class="reading-preview"><h2>How your text will look</h2><p>Every learner grows at their own pace. Read the results, explore the details and choose a helpful next step.</p></div>
    <p class="saved-setting" role="status">{{ options.find(option => option.value === size)?.label }} text selected. Changes are saved automatically.</p>
    <button type="button" :disabled="size === 'standard'" @click="size = 'standard'">Reset to standard</button>
    <p class="settings-note">This browser remembers separate choices for the parent and teacher portals. Printed reports keep their light background and usual text size.</p>
  </section>
</template>

<style scoped>
.display-settings{max-width:900px}.display-settings>p{font-size:1.125rem;line-height:1.7}.display-settings fieldset{border:1px solid #e4d6ef;border-radius:16px;padding:22px;margin:28px 0}.display-settings legend{font-size:1.25rem;font-weight:700;padding:0 8px}.text-size-options{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}.text-size-options label{display:flex;align-items:flex-start;gap:12px;padding:20px 16px;border:2px solid #e7ddf0;border-radius:14px;margin:0;cursor:pointer;background:#fff;min-width:0}.text-size-options label.selected{border-color:#8751be;background:#f5edfc}.text-size-options input{flex-shrink:0;margin-top:4px}.text-size-options strong{font-size:1.125rem}.text-size-options small{display:block;font-size:1rem;line-height:1.6;margin-top:8px;color:#655374}.reading-preview{padding:24px;border-radius:16px;background:#f7f2fc;border:1px solid #e7ddf0}.reading-preview h2{margin-top:0}.reading-preview p{font-size:1.125rem;line-height:1.8;margin-bottom:0}.saved-setting{color:#654487;font-weight:600}.display-settings .settings-note{font-size:1rem;color:#655374;margin-top:24px}@media(max-width:850px){.text-size-options{grid-template-columns:1fr}}@media(max-width:600px){.display-settings fieldset{padding:16px 12px}.reading-preview{padding:20px 16px}}
</style>
