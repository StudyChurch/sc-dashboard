<template>

	<div>

		<card cardBodyClasses="sc-activity--update">
			<activity-form
				elClass="sc-activity--update--form"
				component="groups"
				type="activity_update"
				v-on:activitySaved="addActivity"
				placeholder="Post a message to the group ..."
				:primaryItem="this.groupData.id"
				:secondaryItem="0"
				:autosize="{ minRows: 3 }"></activity-form>
			<div class="category">Use @ to mention a member of this group or use @group to notify everyone.</div>
		</card>

		<div v-loading="loadingActivity" style="min-height: 20em">
			<activity v-for="activity in activityData" :activity="activity" :key="activity.id"></activity>
			<div class="text-center">
				<n-button v-if="activityPage && activityData.length" type="primary" simple="" wide="" v-loading="loadingMoreActivity" @click.native="loadMoreActivity">Load More</n-button>
				<p v-if="! activityPage">There is no more activity to load.</p>
			</div>
		</div>

	</div>

</template>
<script>
  import {
    Card,
    Activity,
    ActivityForm
  } from 'src/components'

  function getDefaultData () {
    return {
      loadingActivity    : true,
      loadingMoreActivity: false,
      activityData       : [],
      activityPage       : 1,
    }
  }

  export default {
    components: {
      Card,
      Activity,
      ActivityForm
    },
    props     : {
      groupData: {id: 0},
    },
    data      : getDefaultData,
    watch     : {
      'groupData' (to, from) {

        // make sure we are dealing with a new group
        if (to.id === from.id) {
          return;
        }

        this.reset();
        this.getGroupActivity();
      }
    },
    mounted() {
      this.getGroupActivity();
    },
    methods   : {
      getGroupActivity () {
        if (!this.groupData.id) {
          return;
        }

        this.$http
          .get(
            '/wp-json/studychurch/v1/activity?component=groups&show_hidden=true&per_page=20&display_comments=threaded&_embed=true&primary_id=' + this.groupData.id + '&page=' + this.activityPage)
          .then(response => {
            if (!response.data.length) {
              this.activityPage = 0;
            }

            this.activityData = this.activityData.concat(response.data)
          })
          .finally(() => this.loadingActivity = this.loadingMoreActivity = false)
      },
      addActivity(newActivity) {
        newActivity.comments = [];
        this.activityData.unshift(newActivity);
      },
      loadMoreActivity () {
        if (!this.activityPage) {
          return false;
        }

        this.loadingMoreActivity = true;
        this.activityPage++;
        this.getGroupActivity();
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