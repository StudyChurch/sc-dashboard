<template>
	<div class="sc-activity--comment--container">

		<div class="sc-activity--comment" v-if="!showUpdateForm">
			<a href="#" v-if="showEditButton" @click.stop="editActivity" class="sc-activity--card--edit">Edit</a>
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

  export default {
    components: {
      ActivityForm
    },
    data() {
      return {
        item  : this.comment,
        update: false
      }
    },
    props     : {
      comment: {},
    },
    mounted() {

    },
    watch     : {},
    computed  : {
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
      },
      updateActivity(activity) {
        this.item.content = activity.content;
        this.item.date = activity.date;
        this.update = false;
      }
    }
  }
</script>
<style>
</style>
