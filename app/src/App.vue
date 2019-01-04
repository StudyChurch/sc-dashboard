<template>
	<div v-loading="! user.me.id" style="min-height:500px;">
		<router-view v-if="user.me.id"></router-view>
	</div>
</template>

<script>
  import { mapState } from 'vuex';

  export default {
    name    : 'App',
    data() {
      return {}
    },
    computed: {
      ...mapState(['user'])
    },
    methods : {}
  }
</script>