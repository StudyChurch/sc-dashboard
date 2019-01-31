<template>
	<div class="panel-header">
		<div class="header text-center">
			<h2 class="title" v-html="study.study.title.rendered"></h2>
			<router-link :to="'/studio/studies/' + this.$route.params.study_id" class="btn btn-primary" v-if="undefined !== this.$route.params.chapter_id">&larr; Back to Study</router-link>
		</div>
	</div>
</template>
<script>
  import { mapState, mapGetters } from 'vuex';

  export default {
    computed  : {
      ...mapState(['study']),
    },
	mounted () {
      this.$store
        .dispatch('study/fetchStudy', {id : this.$route.params.study_id})
        .then(response => {
          this.loading = false
		});
	}
  }</script>
<style>
</style>
