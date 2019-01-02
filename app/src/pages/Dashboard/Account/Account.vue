<template>
	<div class="row">
		<div class="col-lg-4 col-md-5">
			<card class="card-user">
				<div slot="image" class="image">
					<img src="@/assets/img/bg-bible.jpg" alt="...">
				</div>
				<div>
					<div class="author">
						<el-upload class="picture"
								   action="https://jsonplaceholder.typicode.com/posts/"
								   :on-change="handlePreview"
								   :auto-upload="false"
								   :show-file-list="false">
							<img v-if="model.imageUrl" :src="model.imageUrl" class="picture-src avatar border-gray">
						</el-upload>
						<p class="description">
							@{{$root.$data.userData.username}}
						</p>
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
					<fg-input type="email"
							  label="Email"
							  placeholder="Email"
							  v-model="user.email">
					</fg-input>
					<div class="text-center">
						<n-button type="primary" native-type="submit">Save</n-button>
					</div>
				</div>
			</card>
		</div>
		<div class="col-lg-8 col-md-7">
			<edit-profile-form>

			</edit-profile-form>
		</div>
	</div>
</template>
<script>
  import EditProfileForm from './UserProfile/EditProfileForm.vue'
  import UserCard from './UserProfile/UserCard.vue'
  import { Upload } from 'element-ui';

  import {
    Card
  } from 'src/components'

  export default {
    components: {
      EditProfileForm,
      UserCard,
      [Upload.name]: Upload
    },
    data() {
      return {
        model: {
          imageUrl: this.$store.state.user.me.avatar.full
        },
        user : {
          username : this.$store.state.user.me.username,
          email    : this.$store.state.user.me.email,
          firstName: this.$store.state.user.me.firstName,
          lastName : this.$store.state.user.me.lastName,
        }
      }
    },
    created() {},
    computed  : {},
    methods   : {
      handlePreview(file) {
        this.model.imageUrl = URL.createObjectURL(file.raw);
      },

    }
  }
</script>
<style>
</style>
