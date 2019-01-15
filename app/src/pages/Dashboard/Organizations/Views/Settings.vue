<template>

	<div class="sc-group--settings" v-loading="loading">
		<card>
			<h5 slot="header" class="title">Settings</h5>

			<p><img :src="groupData.avatar_urls.full"></p>
			<p>
				<n-button type="primary" simple="" id="pick-avatar">change logo</n-button>
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

			<fg-input type="text"
					  label="Name"
					  v-model="groupSettings.name">
			</fg-input>

			<div class="form-group has-label">
				<label>Description</label>
				<el-input
					ref="description"
					type="textarea"
					:autosize="{ minRows: 4 }"
					resize="none"
					v-model="groupSettings.description"></el-input>
			</div>

			<n-button type="primary" @click.native="updateGroup">Save</n-button>

		</card>
	</div>

</template>
<script>
  import { Input } from 'element-ui';
  import { mapState } from 'vuex';
  import AvatarCropper from "vue-avatar-cropper"

  import {
    Card,
    Table as NTable,
    Button
  } from 'src/components'

  export default {
    components: {
      Card,
      NTable,
      Button,
      Input,
      'avatar-cropper': AvatarCropper
    },
    data() {
      return {
        loading      : false,
        imgDataUrl   : '',
        groupSettings: {
          name       : '',
          description: ''
        }
      }
    },
    computed  : {
      ...mapState(['group', 'user']),
      groupData() {
        return this.group.organization;
      }
    },
    watch     : {
      'groupData' (to, from) {

        // make sure we are dealing with a new group
        if (to.id === from.id) {
          return;
        }

        this.groupSettings = {
          name       : this.groupData.name,
          description: this.groupData.description.raw
        };
      }
    },
    mounted() {
      this.groupSettings = {
        name       : this.groupData.name,
        description: this.groupData.description.raw
      };
    },
    methods   : {
      updateGroup() {
        this.loading = true;
        return this.$store
          .dispatch('group/updateGroup', {groupID: this.groupData.id, data: this.groupSettings})
          .then(() => {
            this.loading = false;
          })
      },
      uploadHandler(cropper) {
        console.log(this.$refs.avatar.filename.indexOf('png'));
        cropper.getCroppedCanvas(this.outputOptions).toBlob((blob) => {
          let formData = new FormData();

          formData.append('file', blob, this.$refs.avatar.filename);
          formData.append('title', this.groupData.name + ' avatar');
          formData.append('action', 'bp_avatar_upload');

          this.$store
            .dispatch('group/updateAvatar', {groupID: this.groupData.id, data: formData})
            .then((response) => {
              console.log(response);
            });
        }, -1 === this.$refs.avatar.filename.indexOf('png') ? 'image/jpeg' : 'image/png', 0.9)
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
    }
  }
</script>
<style>
</style>