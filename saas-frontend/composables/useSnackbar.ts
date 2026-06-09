import { ref } from 'vue';

const show = ref(false);
const message = ref('');
const color = ref('');

export function useSnackbar() {
  function showSnackbar(msg: string, type = 'success') {
    message.value = msg;
    color.value = type;
    show.value = true;
  }

  return {
    show,
    message,
    color,
    showSnackbar,
  };
}
