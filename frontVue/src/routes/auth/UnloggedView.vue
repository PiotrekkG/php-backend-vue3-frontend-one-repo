<template>
  <div class="text-center">
    <router-link to="/guest" class="btn btn-info">Dołącz jako gość (bez rejestracji)</router-link>
    lub
    <div class="btn-group">
      <router-link to="/login" class="btn btn-primary">Zaloguj się</router-link>
      <router-link to="/register" class="btn btn-secondary">Zarejestruj się</router-link>
    </div>
  </div>
</template>

<script>
export default {
  name: 'HomeView',
  data() {
    return {};
  },
  computed: {},
  methods: {},
  created() {},
  mounted() {},
}
</script>