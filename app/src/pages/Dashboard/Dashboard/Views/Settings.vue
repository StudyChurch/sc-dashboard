<template>
	<card v-loading="loading">
		<h5 slot="header" class="title">Edit Profile</h5>
		<div class="row">
			<div class="col-md-4">
				<fg-input type="email"
						  label="Email"
						  placeholder="Email"
						  v-model="userSettings.email">
				</fg-input>
			</div>
		</div>

		<div class="row">
			<div class="col-md-6">
				<fg-input type="text"
						  label="Name"
						  placeholder="Name"
						  v-model="userSettings.name">
				</fg-input>
			</div>
		</div>

		<n-button type="primary" @click.native="updateProfile">Save</n-button>

	</card>
</template>
<script>
  import { mapState } from 'vuex';

  export default {
    data() {
      return {
        loading     : false,
        userSettings: {
          email    : '',
          firstName: '',
          lastName : ''
        }
      }
    },
    computed: {
      ...mapState(['user']),
    },
    methods : {
      updateProfile() {
        this.loading = true;

        return this.$store
          .dispatch('user/updateUser', {userID: this.user.me.id, data: this.userSettings})
          .then(() => this.loading = false);
      }
    },
    mounted() {
      this.userSettings = {
        email: this.user.me.email,
        name : this.user.me.name,
      }
    }
  }

</script>
<style>

</style>
