<template>
  <div class="container">
    <div v-if="dbinfo !== null">
      {{ dbinfo }}
    </div>
    <div v-else>
      Ładowanie...
    </div>
  </div>
</template>

<script>
import { dateString, fetchData, handleRequestResponse, timeString } from '@/functions/Utils';
import { useAuthStore } from '@/stores/auth';

export default {
  name: 'LoggedView',
  data() {
    return {
      authStore: useAuthStore(),

      dbinfo: null,
    };
  },
  computed: {
  },
  methods: {
    timeString,

    async fetchData() {
      let response;
      // response = await fetchData('/info');
      // handleRequestResponse(response, () => {
      //   this.output = `Aktualny czas ${dateString(response.data.time * 1000)} ${timeString(response.data.time * 1000, true)}.`;
      // }, () => {
      //   alert('Błąd przy pobieraniu info');
      // });

      response = await fetchData('/db');
      handleRequestResponse(response, () => {
        this.dbinfo = response.data.tables;
      }, () => {
        alert('Błąd przy pobieraniu info');
      });
    },
  },
  async mounted() {
    await this.fetchData();
  },
}
</script>

<style scoped>
#canvas {
  display: block;
  background-color: #fff;
  width: 1000px;
  height: 600px;
  margin: 20px auto;
  /* margin-top: 300px; */
}
</style>