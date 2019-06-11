<template>
	<div class="sc-activity--comment--container">

		<div class="sc-activity--comment" v-if="!showUpdateForm" v-loading="loading">
			<div class="sc-activity--comment--child-actions">
				<a href="#" v-if="showEditButton" @click.prevent="editActivity">
					<n-button
							class="edit btn-neutral"
							type="info"
							size="sm" icon>
						<font-awesome-icon icon="edit"></font-awesome-icon>
					</n-button>
				</a>
				<el-popover
					v-model="deleteModal"
					placement="top">
					<p>Are you sure you want to delete this comment?</p>
					<div>
						<n-button size="sm" type="text" @click.native="deleteModal = false">cancel</n-button>
						<n-button type="danger" size="sm" @click.native="deleteActivity">delete</n-button>
					</div>
					<n-button
							slot="reference"
							class="remove btn-neutral"
							type="danger"
							size="sm" icon v-if="showEditButton || isGroupAdmin()">
						<font-awesome-icon icon="times"></font-awesome-icon>
					</n-button>
				</el-popover>

			</div>

			<img class="avatar border-gray" :src="item.user_avatar.full">
			<p class="category" style="margin-bottom:0;">
				{{ item.date | dateFormat }} | <span v-html="item.title"></span>
			</p>
			<div v-html="item.content.rendered"></div>
		</div>

		<activity-form
			v-if="showUpdateForm"
			elClass="sc-activity--comment"
			ref="activityForm"
			:activityID="this.item.id"
			:component="this.item.component"
			:type="this.item.type"
			v-on:activitySaved="updateActivity"
			v-on:activityCanceled="cancelUpdate"
			:primaryItem="this.item.prime_association"
			:secondaryItem="this.item.secondary_association"></activity-form>

	</div>
</template>
<script>
  import ActivityForm from './ActivityForm.vue';
  import { Input } from 'element-ui';
  import ActivityService from '@/services/ActivityService.js';
  import { mapGetters } from 'vuex';

  export default {
    components: {
      ActivityForm,
      Input,
    },
    data() {
      return {
        item  : this.comment,
        update: false,
		  loading: false,
		  deleteModal: false,
      }
    },
    props     : {
      comment: {},
    },
    mounted() {

    },
    watch     : {},
    computed  : {
        ...mapGetters('group', ['isGroupAdmin']),

      showEditButton() {
        return undefined !== this.item.content.raw && this.item.user === this.$store.state.user.me.id;
      },
      showUpdateForm() {
        return undefined !== this.item.content.raw && this.update && this.item.user === this.$store.state.user.me.id;
      },
    },
    methods   : {
      editActivity(e) {
        e.preventDefault();
        this.update = true;
        this.$nextTick(() => {
          // @todo, comments don't always have content.raw. If they don't, we need to run an api request
          this.$refs.activityForm.updateComment(this.item.content.raw);
          this.$refs.activityForm.setFocus();
        })
      },
      cancelUpdate(e) {
        this.update = false;
      },
      addComment(comment) {
        this.item.comments.push(comment);
        this.$root.reftag();
      },
      updateActivity(activity) {
        this.item.content = activity.content;
        this.item.date = activity.date;
        this.update = false;
      },
		deleteActivity() {
          this.loading = true;
           ActivityService.deleteActivity( this.item ).then( response => {
            	this.$emit('activityDeleted', response.data );
            	this.loading = false;
        	} );

		}
    }
  }
</script>
<style scoped>
	.sc-activity--comment--child-actions {
		display: inline-block;
		float: right;
	}

	.sc-activity--comment--child-actions .btn-neutral {
		background: transparent;
	}
</style>