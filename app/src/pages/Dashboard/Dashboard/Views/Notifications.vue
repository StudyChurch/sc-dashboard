<template>

	<div class="sc-dashboard--notifications sc-notifications" style="min-height:200px;" v-loading="loading">

	</div>

</template>
<script>
  import {
    Card,
    Table as NTable,
    Button
  } from 'src/components'

  export default {
    components: {
      Card,
      NTable,
      Button
    },
	data() {
      return {
        loading: true,
        notifications: []
	  }
	},
	mounted() {
      this.getNotifications();
	},
    computed  : {},
    methods   : {
      getGroup(id) {
        return this.$root.userData.groups.filter(group => group.id === id)[0];
	  },
      getNotifications () {
        this.$http
          .get(
            '/wp-json/buddypress/v1/notifications?user_id=8')
          .then(response => {
            console.log(response);
            this.notifications = response.data;
          })
          .finally(() => this.loading = false)
      },
      reset (keep) {
        let def = getDefaultData();
        def[keep] = this[keep];
        Object.assign(this.$data, def);
      }
    }
  }
</script>
<style>
</style>