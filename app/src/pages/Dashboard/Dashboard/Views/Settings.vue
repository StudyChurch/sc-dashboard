<template>
	<card v-loading="loading">
		<h5 slot="header" class="title">Edit Profile</h5>

		<p><img :src="this.user.me.avatar_urls.full"></p>
		<p>
			<n-button type="primary" simple="" id="pick-avatar">change avatar</n-button>
		</p>

		<avatar-cropper
			ref="avatar"
			@uploading="handleUploading"
			@uploaded="handleUploaded"
			@completed="handleCompleted"
			@error="handlerError"
			:uploadHandler="uploadHandler"
			:labels="{ submit: 'save', cancel: 'cancel'}"
			trigger="#pick-avatar" />
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
  import AvatarCropper from "vue-avatar-cropper"

  export default {
    components: {
      'avatar-cropper': AvatarCropper
    },
    data() {
      return {
        loading     : false,
        imgDataUrl  : '',
        userSettings: {
          email    : '',
          firstName: '',
          lastName : ''
        },
      }
    },
    computed  : {
      ...mapState(['user']),
    },
    methods   : {
      updateProfile() {
        this.loading = true;

        return this.$store
          .dispatch('user/updateUser', {userID: this.user.me.id, data: this.userSettings})
          .then(() => this.loading = false);
      },
      uploadHandler(cropper) {
        cropper.getCroppedCanvas(this.outputOptions).toBlob((blob) => {
          let formData = new FormData();

          formData.append('file', blob, this.$refs.avatar.filename);
          formData.append('title', this.user.me.name + ' avatar');
          formData.append('action', 'bp_avatar_upload');

          this.$store
			.dispatch('user/updateAvatar', {userID : this.user.me.id, data : formData})
            .then((response) => {
              console.log(response);
            });
        }, 'image/jpeg', 0.9)
      },
      handleUploading(form, xhr) {
        this.message = "uploading...";
      },
      handleUploaded(response) {
        if (response.status == "success") {
          this.imgDataUrl = response.url;
          // Maybe you need call vuex action to
          // update user avatar, for example:
          // this.$dispatch('updateUser', {avatar: response.url})
          this.message = "user avatar updated.";
        }
      },
      handleCompleted(response, form, xhr) {
        this.message = "upload completed.";
      },
      handlerError(message, type, xhr) {
        this.message = "Oops! Something went wrong...";
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
