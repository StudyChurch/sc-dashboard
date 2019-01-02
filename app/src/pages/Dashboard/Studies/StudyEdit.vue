<template>
	<div>

	</div>

</template>
<script>
  import {
    Card,
    Table as NTable,
    Progress as NProgress,
    AnimatedNumber,
    TimeLine,
    TimeLineItem
  } from 'src/components';

  import Answer from './Elements/Answer.vue';

  export default {
    components: {
      Card,
      NTable,
      NProgress,
      AnimatedNumber,
      TimeLine,
      TimeLineItem,
      Answer
    },
    data() {
      return {
        loading     : true,
        prevChapter : {
          id: 0
        },
        nextChapter : {
          id: 0
        },
        chapters    : [],
        chapterData : {
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
        studyData   : {
          id         : 0,
          name       : '',
          slug       : '',
          title      : {
            rendered: ''
          },
          avatar_urls: {
            full : '',
            thumb: ''
          },
          description: {
            rendered: ''
          }
        },
        activityData: [],
      }
    },
    mounted() {
      this.getChapterItems();
      this.getStudyChapters();
    },
    watch     : {
      '$route' (to, from) {
        this.getChapterItems();
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

        return '/groups/' + this.$route.params.slug;
      },
    },
    methods   : {
      getStudyChapters () {
        this.$http
          .get(
            '/wp-json/studychurch/v1/studies/' + this.$route.params.study + '/chapters')
          .then(response => {
            this.chapters = response.data;
            this.getChapterItems();
          })
          .finally(() => this.loading = false)
      },
      getChapterItems () {
        let i = 0;

        for (i = 0; i < this.chapters.length; i++) {
          if (undefined === this.$route.params.chapter || this.chapters[i].slug === this.$route.params.chapter) {
            this.chapterData = this.chapters[i];
            break;
          }
        }

        if (i > this.chapters.length) {
          return;
        }

        if (i > 0) {
          if (undefined !== this.chapters[i - 1]) {
            this.prevChapter = this.chapters[i - 1];
          }
        }

        if (i < this.chapters.length) {
          if (undefined !== this.chapters[i + 1]) {
            this.nextChapter = this.chapters[i + 1];
          }
        }

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
