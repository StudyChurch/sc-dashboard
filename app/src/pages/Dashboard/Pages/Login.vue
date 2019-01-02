<template>
  <div class="col-md-4 ml-auto mr-auto">

    <form @submit.prevent="validate">
      <card class="card-login card-plain">
        <div slot="header">
          <div class="logo-container">
            <img src="@/assets/img/now-logo.png" alt="">
          </div>
        </div>
        <div>

          <fg-input class="no-border form-control-lg"
                    required
                    v-model="model.email"
                    v-validate="modelValidations.email"
                    :error="getError('email')"
                    placeholder="Email ..."
                    addon-left-icon="now-ui-icons users_circle-08"
                    name="email"
                    type="email">
          </fg-input>

          <fg-input class="no-border form-control-lg"
                    required
                    name="password"
                    v-model="model.password"
                    v-validate="modelValidations.password"
                    placeholder="Password ..."
                    addon-left-icon="now-ui-icons objects_key-25"
                    :error="getError('password')"
                    type="password">
          </fg-input>

          <n-button native-type="submit" type="primary" round block>Login</n-button>

        </div>
      </card>
    </form>

    <div slot="footer">
      <div class="pull-left">
        <h6>
          <router-link class="link footer-link" to="/register">
            Create Account
          </router-link>
        </h6>
      </div>

      <div class="pull-right">
        <h6><a href="#pablo" class="link footer-link">Need Help?</a></h6>
      </div>
    </div>


  </div>
</template>
<script>
  import { login } from '../../../auth';

  export default {
    data () {
      return {
        model: {
          email: '',
          password: '',
        },
        modelValidations: {
          email: {
            required: true,
            email: true
          },
          password: {
            required: true,
            min: 5
          }
        }
      }
    },
    methods: {
      getError (fieldName) {
        return this.errors.first(fieldName)
      },
      validate () {
        this.$validator.validateAll().then(isValid => {
          if (isValid) {
            this.submit();
          }
        })
      },
      submit () {
        console.log( 'submitting123' );
        login(this.model.email, this.model.password);
      }
    }
  }
</script>

<style>
  .navbar-nav .nav-item p {
    line-height: inherit;
    margin-left: 5px;
  }
</style>
