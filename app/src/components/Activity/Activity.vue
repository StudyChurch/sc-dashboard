<template>
	<card class="card-chart sc-activity--card" no-footer-line>

		<div slot="header" style="position:relative;padding-left:40px;">
			
			<div class="sc-activity--comment--parent-actions">
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
					<p>Are you sure you want to delete this entire activity thread?</p>
					<div>
						<n-button size="sm" type="text" @click.native="deleteModal = false">cancel</n-button>
						<n-button type="danger" size="sm" @click.native="deleteActivity">delete</n-button>
					</div>
					<n-button
							slot="reference"
							class="remove btn-neutral"
							type="danger"
							size="sm" icon v-if="( showEditButton || isGroupAdmin() ) && item.type != 'answer_update'">
						<font-awesome-icon icon="times"></font-awesome-icon>
					</n-button>
				</el-popover>
			</div>

			<img class="avatar border-gray" :src="item.user_avatar.full" alt="..." style="position: absolute;left:0;">
			<div class="card-category" v-html="item.title"></div>
			<div class="card-category">{{item.date | dateFormat }}</div>
		</div>

		<activity-form
			v-if="showUpdateForm"
			elClass="sc-activity--card--update"
			ref="activityForm"
			:activityID="this.item.id"
			:component="this.item.component"
			:type="this.item.type"
			v-on:activitySaved="updateActivity"
			v-on:activityCanceled="cancelUpdate"
			:primaryItem="this.item.prime_association"
			:secondaryItem="this.item.secondary_association"></activity-form>

		<div v-if="showActivityContent" v-html="item.content.rendered"></div>

		<activity-comment v-if="showActivityContent" v-for="comment in getComments" :comment="comment" :key="comment.id" v-on:activityDeleted="activityDeleted" v-loading="loadingActivity"></activity-comment>

		<activity-form
			v-show="showCommentForm"
			elClass="sc-activity--comment"
			component="activity"
			type="activity_comment"
			v-on:activitySaved="addComment"
			:primaryItem="this.item.id"
			:secondaryItem="this.item.id"></activity-form>

	</card>
</template>
<script>
  import Card from '../Cards/Card.vue';
  import ActivityForm from './ActivityForm.vue';
  import ActivityComment from './ActivityComment.vue';
  import ActivityService from '@/services/ActivityService.js';
  import { mapState, mapGetters } from 'vuex';

  export default {
    components: {
      Card,
      ActivityForm,
      ActivityComment
    },
    data() {
      return {
        item  : this.activity,
        update: false,
		  deleteModal: false,
		  loadingActivity: false,
      }
    },
    props     : {
      activity   : {},
      showForm   : {
        type   : [Boolean],
        default: false,
      },
      showContent: {
        type   : [Boolean],
        default: false,
      }
    },
    mounted() {

    },
    watch     : {},
    computed  : {
      ...mapState(['user', 'group']),
      ...mapGetters('group', ['isGroupAdmin']),

      getComments() {
        if (undefined === this.item.comments) {
          return [];
        }

        return this.item.comments;
      },
      showActivityContent() {
        if (this.showUpdateForm) {
          return false;
        }

        if (this.showContent) {
          return true;
        }

        return (
          this.item.content.rendered && (
            this.getComments.length > 0 || 'answer_update' !== this.item.type
          )
        );
      },
      showCommentForm() {

        if (this.showUpdateForm) {
          return false;
        }

        if (this.showForm) {
          return true;
        }

        if (this.getComments.length > 0 && 'answer_update' === this.item.type) {
          return true;
        }

        if ('activity_update' === this.item.type) {
          return true;
        }

        return false;
      },
      showUpdateForm() {
        return this.update && this.item.user === this.user.me.id;
      },
      showEditButton() {
        return this.showActivityContent && this.item.user === this.user.me.id;
      },
    },
    methods   : {
      editActivity(e) {
        e.preventDefault();
        this.update = true;
        this.$nextTick(() => {
          this.$refs.activityForm.updateComment(this.item.content.raw);
          this.$refs.activityForm.setFocus();
        });
      },
		deleteActivity() {
          this.loadingActivity = true;
          ActivityService.deleteActivity( this.item ).then( response => {
			this.$parent.reset();
			this.$parent.getGroupActivity();
            this.loadingActivity = true;
		  } );
		},
      cancelUpdate(e) {
        this.update = false;
      },
      addComment(comment) {
        if (undefined === this.item.comments) {
          this.item.comments = [];
        }

        this.item.comments.push(comment);
      },
      updateActivity(activity) {
        this.item.content = activity.content;
        this.item.date = activity.date;
        this.update = false;
      },
		activityDeleted(activity) {
          let index = this.item.comments.findIndex( x => x.id === activity.id );
          this.item.comments.splice( index, 1 );
		}
    }
  }
</script>
<style scoped>
	.sc-activity--comment--parent-actions {
		display: inline-block;
		float: right;
	}
</style>
