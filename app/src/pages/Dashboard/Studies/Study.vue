<template>
	<div>
		<card v-loading="loading" style="min-height: 200px;">
			<div class="card-header">
				<div class="study-meta float-right">
					<el-select class="select-primary" size="small" placeholder="Select Chapter" v-if="studyData.navigation.length" v-model="studyData.currentChapter" style="margin:-10px -5px">
						<el-option
							v-for="option in studyData.navigation"
							class="select-primary"
							:value="getChapterLink(option)"
							:label="option.title.rendered"
							:key="option.id">
						</el-option>
					</el-select>
				</div>
				<div>
					<h5 class="title" v-html="studyData.name"></h5>
					<h1 class="title" v-html="chapterData.title.rendered"></h1>
				</div>
			</div>

			<div v-for="data in chapterData.elements" :id="'post-' + data.id">
				<div class="card-body" v-html="data.content.rendered"></div>
				<div v-if="data['data_type'] === 'question_short' ||  data['data_type'] === 'question_long'" class="card-footer">
					<div class="sc-answer" v-if="isPreview">
						<activity-form
							ref="answerForm"
							:disabled="true"
							elClass="sc-activity--answer"
							component="groups"
							type="answer_update"
							placeholder="This study is in preview mode."
							:primaryItem="0"></activity-form>
					</div>
					<answer v-else :questionData="data" :groupData="groupData"></answer>
				</div>
			</div>
		</card>

		<div>
			<router-link v-if="studyData.prevChapter.id && chapterData.id !== studyData.prevChapter.id" :to="navPrefix + $root.cleanLink(studyData.prevChapter.link)" tag="button" class="btn btn-default">
				<span class="btn-label btn-label-right"><i class="now-ui-icons arrows-1_minimal-left"></i></span>
				&nbsp;&nbsp;<span v-html="studyData.prevChapter.title.rendered"></span>
			</router-link>
			<router-link v-if="studyData.nextChapter.id && chapterData.id !== studyData.nextChapter.id" :to="navPrefix + $root.cleanLink(studyData.nextChapter.link)" tag="button" class="btn btn-default float-right">
				<span v-html="studyData.nextChapter.title.rendered"></span>
				&nbsp;&nbsp;<span class="btn-label btn-label-right"><i class="now-ui-icons arrows-1_minimal-right"></i></span>
			</router-link>
		</div>
	</div>

</template>
<script>
  import {
    Card,
    Table as NTable,
    Progress as NProgress,
    AnimatedNumber,
    TimeLine,
    TimeLineItem,
	ActivityForm
  } from 'src/components';

  import { Select, Option } from 'element-ui';
  import Answer from './Elements/Answer.vue';

  function getDefaultData () {
    return {
      loading    : true,
      chapterData: {
        id      : 0,
        study   : '',
        title   : {
          rendered: '',
        },
        elements: [
          {
            content: {
              rendered: ''
            }
          }
        ],
      },
      studyData  : {
        name          : '',
        currentChapter: '',
        prevChapter   : {},
        nextChapter   : {},
        navigation    : []
      },
    }
  }

  export default {
    components: {
      Card,
      NTable,
      NProgress,
      AnimatedNumber,
      TimeLine,
      TimeLineItem,
      Answer,
	  ActivityForm,
      'el-select': Select,
      'el-option': Option
    },
    data      : getDefaultData,
    mounted() {
      this.getNavigation();
      this.getChapter();
    },
    watch     : {
      '$route' (to, from) {
        this.setupNavigation();

        if (to.params.study !== from.params.study) {
          this.getNavigation();
        }

        if (to.params.chapter !== from.params.chapter) {
          this.getChapter();
        }
      },
      'studyData.currentChapter' (to) {
        if (to !== this.$route.path) {
          this.$router.push(to);
        }
      }
    },
    props     : {
      groupData: {
        default() {
          return {
            id     : 0,
            studies: []
          }
        }
      },
    },
    computed  : {
      navPrefix() {
        if (!this.groupData.id) {
          return '';
        }

        if (undefined === this.$route.params.slug) {
          return '';
        }

        let prefix = this.isOrganization ? '/organizations/' : '/groups/';

        return prefix + this.$route.params.slug;
      },
      isOrganization() {
        return this.$route.path.includes('organizations');
      },
	  isPreview() {
        return this.isOrganization;
	  }
    },
    methods   : {
      getChapterLink(chapter) {
        return this.navPrefix + this.$root.cleanLink(chapter.link);
      },
      getNavigation() {
        this.reset();

        this.$http
          .get('/wp-json/studychurch/v1/studies/' + this.$route.params.study + '/navigation')
          .then(response => {
            this.studyData.navigation = response.data;
            this.maybeRedirectToFirstChapter();
            this.setupNavigation();
          })
      },
      maybeRedirectToFirstChapter() {
        if (undefined === this.$route.params.chapter && this.studyData.navigation.length) {
          this.$router.push(this.getChapterLink(this.studyData.navigation[0]));
        }
      },
      getChapter() {

        if (undefined === this.$route.params.chapter) {
          return;
        }

        this.reset('studyData');

        this.$http
          .get(
            '/wp-json/studychurch/v1/studies/' + this.$route.params.study + '/chapters/' + this.$route.params.chapter)
          .then(response => {
            this.chapterData = response.data;
            this.studyData.name = this.chapterData.study;
          })
          .finally(() => this.loading = false)
      },
      setupNavigation () {
        let i = 0;

        this.studyData.currentChapter = this.$route.path;

        for (i = 0; i < this.studyData.navigation.length; i++) {
          if (undefined === this.$route.params.chapter || this.studyData.navigation[i].slug === this.$route.params.chapter) {
            break;
          }
        }

        if (i > this.studyData.navigation.length) {
          return;
        }

        if (i > 0) {
          if (undefined !== this.studyData.navigation[i - 1]) {
            this.studyData.prevChapter = this.studyData.navigation[i - 1];
          } else {
            this.studyData.prevChapter = {id: 0};
          }
        }

        if (i < this.studyData.navigation.length) {
          if (undefined !== this.studyData.navigation[i + 1]) {
            this.studyData.nextChapter = this.studyData.navigation[i + 1];
          } else {
            this.studyData.nextChapter = {id: 0};
          }
        }

      },
      reset (keep) {
        let def = getDefaultData();
        def[keep] = this[keep];
        Object.assign(this.$data, def);
      }
    }
  }
</script>
<style scoped>

	.study-meta {
		display: flex;
		flex-wrap: wrap;
		justify-content: left;
	}

	.study-meta div {
		margin: 0 .5rem;
	}
</style>
