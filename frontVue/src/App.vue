<template>
  <nav class="navbar navbar-expand-sm fixed-top bg-body-tertiary" data-bs-theme="dark">
    <div class="container">
      <a class="navbar-brand" href="#">Example</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
        aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul v-if="authStore.user" class="navbar-nav ms-auto mb-2 mb-lg-0">
          <li class="nav-item">
            <router-link class="nav-link" :to="{ name: 'Logged' }" active-class="active">App</router-link>
          </li>
          <li>
            <button class="btn btn-primary btn-sm" @click="authStore.logout()">Wyloguj [{{ authStore.user?.username }}]</button>
          </li>
        </ul>
        <ul v-else class="navbar-nav ms-auto mb-2 mb-lg-0">
          <li class="nav-item">
            <router-link class="nav-link" :to="{ name: 'UnloggedView' }" active-class="active">Logowanie</router-link>
          </li>
          <li class="nav-item">
            <router-link class="nav-link" :to="{ name: 'Guest' }" active-class="active">Jako gość</router-link>
          </li>
        </ul>
      </div>
      <!-- <div class="text-end" v-if="authStore.user">
        Zalogowano jako {{ authStore.user?.username }} ({{ authStore.user?.isGuest ? 'Gość' : 'Zalogowany' }})
        <button class="btn btn-primary btn-sm" @click="authStore.logout()">Wyloguj</button>
      </div> -->
    </div>
  </nav>
  
  <div class="container mt-5 pt-3">
    <!-- <h1 class="text-center">Example</h1> -->
    <router-view />

    <hr class="mt-4" /> Logged user info: {{ authStore.user ?? 'null' }}

    <InfoViewer />
  </div>
</template>

<script>
import InfoViewer from './Components/InfoViewer.vue';
import { useAuthStore } from './stores/auth';

export default {
  name: 'App',
  data() {
    return {
      authStore: useAuthStore(),
    };
  },
  components: {
    InfoViewer,
  },
  computed: {},
  methods: {},
  created() { },
  mounted() { },
}
</script>

<style>
body {
  background-color: #f8c464 !important;
}
</style>