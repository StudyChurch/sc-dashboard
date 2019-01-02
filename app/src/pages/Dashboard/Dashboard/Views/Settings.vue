<template>
  <card>
    <h5 slot="header" class="title">Edit Profile</h5>
    <form>
      <div class="row">
        <div class="col-md-3">

          <fg-input type="text"
                    label="Username"
                    placeholder="Username"
                    :disabled="true"
                    v-model="user.username">
          </fg-input>
        </div>
        <div class="col-md-4">
          <fg-input type="email"
                    label="Email"
                    placeholder="Email"
                    v-model="user.email">
          </fg-input>
        </div>
      </div>

      <div class="row">
        <div class="col-md-6">
          <fg-input type="text"
                    label="First Name"
                    placeholder="First Name"
                    v-model="user.firstName">
          </fg-input>
        </div>
        <div class="col-md-6">
          <fg-input type="text"
                    label="Last Name"
                    placeholder="Last Name"
                    v-model="user.lastName">
          </fg-input>
        </div>
      </div>
    </form>
  </card>
</template>
<script>
  export default {
    data() {
      return {
        user: {
          username: this.$store.state.user.me.username,
          email: this.$store.state.user.me.email,
          firstName: this.$store.state.user.me.firstName,
          lastName: this.$store.state.user.me.lastName,
        }
      }
    },
    methods: {
      updateProfile() {
        alert('Your data: ' + JSON.stringify(this.user))
      }
    }
  }

</script>
<style>

</style>
