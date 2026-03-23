<template>
  <div class="text-center">
    <h2 class="mt-5 mb-3">Dołącz jako gość</h2>
    <p class="mb-0">Dołącz bez rejestracji!</p>

    <form class="w-50 mx-auto" @submit="submitGuestLogin">
      Wprowadź swój pseudonim:
      <div class="input-group w-100 mb-3">
        <span class="input-group-text text-bg-dark"><i class="bi bi-person"></i></span>
        <input type="text" class="form-control" placeholder="Twój pseudonim" v-model="username" />
        <span class="input-group-text">@random</span>
      </div>
      <button class="btn btn-success w-100">Dołącz</button>
    </form>
    <p class="mt-3">Chcesz mieć możliwość zapisywania wyników i postępów? <a href="#" @click="$router.push({ name: 'Register' })">Zarejestruj się</a></p>
    <p class="mt-1">Już posiadasz konto? <a href="#" @click="$router.push({ name: 'Login' })">Zaloguj się</a></p>
  </div>
</template>

<script>
import { handleRequestResponse } from '@/functions/Utils';
import { useAuthStore } from '@/stores/auth';

export default {
  name: 'GuestLoginView',
  data() {
    return {
      authStore: useAuthStore(),

      username: 'User',
    };
  },
  computed: {},
  methods: {
    async submitGuestLogin(event) {
      event.preventDefault();
      
      const response = await this.authStore.loginAsGuest(this.username);
      
      handleRequestResponse(response, () => {
        console.log(`Dołączasz jako: ${this.username}`);
        this.$router.push({ name: 'Logged'});
      }, () => {
        alert('Nie udało się dołączyć jako gość. Spróbuj ponownie.');
      });

    }
  },
  created() {},
  mounted() {},
}
</script>