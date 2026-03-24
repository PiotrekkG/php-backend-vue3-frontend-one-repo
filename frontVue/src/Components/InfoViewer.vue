<template>
    <VModal :show-state="anyAlert" ref="alertModal" @show="checkAlerts()" @closed="checkAlerts(true)">
      <template #title>
        <h5 v-if="currentAlert" class="modal-title">
          <i
            :class="`bi bi-${currentAlert.type === 'error' ? 'exclamation-triangle-fill text-danger' : currentAlert.type === 'success' ? 'check-circle-fill text-success' : 'info-circle-fill text-info'}`"></i>
          <span class="ms-2">{{ currentAlert.title || (currentAlert.type === 'error' ? 'Błąd' : currentAlert.type ===
            'success' ? 'Sukces' : 'Informacja') }}</span>
        </h5>
      </template>
      <template #body>
        <p v-if="currentAlert">{{ currentAlert.message }}</p>
      </template>
      <template #footer>
        <button type="button" class="btn btn-primary" @click="$refs.alertModal.hide()">OK</button>
      </template>
    </VModal>

    <div class="toast-container position-fixed top-50 start-50 translate-middle p-3">
      <template v-for="(toast, index) in toasts" :key="index">
        <VToast v-if="toast" :info-text="toast.message" :info-type="toast.type" :only-text="false"
          @remove="removeToast(toast)" />
      </template>
    </div>
</template>

<script>
import VModal from './VModal.vue';
import VToast from './VToast.vue';
import { useInfoStore } from '../stores/info';

export default {
  name: "InfoViewer",
  data() {
    return {
      infoStore: useInfoStore(),

      currentAlert: null,
      toasts: [],
    }
  },
  computed: {
    anyAlert() {
      return this.infoStore.anyAlert;
    },
    anyToast() {
      return this.infoStore.anyToast;
    },
  },
  components: {
    VModal,
    VToast,
  },
  methods: {
    checkAlerts(clear = false) {
      if (clear)
        this.currentAlert = null;
      if (this.anyAlert) {
        this.currentAlert = this.infoStore.getAlert();
        this.$refs.alertModal.show();
      }
    },
    checkToasts() {
      while (this.anyToast) {
        const toast = this.infoStore.getToast();
        this.toasts.push(toast);
      }
    },
    removeToast(toast) {
      const index = this.toasts.indexOf(toast);
      if (index > -1) {
        this.toasts[index] = undefined;
      }
    }
  },
  mounted() {
    this.$watch(
      () => this.infoStore.anyToast,
      (newVal) => {
        if (newVal) {
          this.checkToasts();
        }
      }
    );
  },
};
</script>
