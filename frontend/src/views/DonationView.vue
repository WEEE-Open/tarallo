<script>
import { toRaw } from "vue";


export default {
  props: ["donation"],
  data() {
    return {
      counter: 0,
    }
  },
  mounted() {
    console.log(toRaw(this.donation));
  }
}
</script>

<template>
  <v-app>
    test {{ donation.title }}
    <button @click="counter++">asd {{ counter }}</button>
  </v-app>
</template>
