<script setup>
const { data } = await useFetch('http://127.0.0.1:8000/api/ping')
</script>

<template>
  <div>
    <h1>Backend says: {{ data?.message }}</h1>
  </div>
</template>