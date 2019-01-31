<template>
	<div class="sc-studio--study-edit">

		<div class="row">
			<div class="col-lg-8">
				<card v-loading="loading">
					<h6>Title</h6>
					<p>
						<el-input
							type="text"
							v-model="studyData.title"
						></el-input>
					</p>

					<h6>Description</h6>
					<froala :tag="'textarea'" :config="config" v-model="studyData.content"></froala>

					<p>
						<n-button type="primary" @click.native="saveStudy">Save</n-button>
					</p>

					<hr />
					<div class="sc-studio--study-edit--navigation">
						<img slot="image" :src="study.study.thumbnail">
					</div>
				</card>
			</div>

			<div class="col-lg-4">
				<card v-loading="loadingChapters">
					<h6>Chapters</h6>
					<draggable v-model="navigation" :options="{draggable: '.item', handle : '.drag-item'}" @end="saveNavigation">
						<div v-for="item in navigation" :key="item.id" class="item">
							<p>
								<a class="float-right drag-item" href="#">
									<font-awesome-icon icon="arrows-alt"></font-awesome-icon>
								</a>
								<router-link :to="getChapterLink(item)" v-html="item.title.rendered"></router-link>
							</p>
						</div>
					</draggable>

					<form @submit.prevent="createChapter">
						<el-input
							type="text"
							v-model="newChapter"
							placeholder="Add a new chapter"
							v-loading="creatingChapter"></el-input>
					</form>

				</card>
			</div>
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

  import draggable from 'vuedraggable';
  import Chapter from '../Components/Chapter';
  import { Select, Option } from 'element-ui';
  import { mapState, mapGetters } from 'vuex';
  import ElInput from '../../../../../node_modules/element-ui/packages/input/src/input';

  export default {
    components: {
      ElInput,
      Card,
      NTable,
      NProgress,
      AnimatedNumber,
      TimeLine,
      TimeLineItem,
      ActivityForm,
      'el-select': Select,
      'el-option': Option,
      Chapter,
      draggable
    },
    data      : function () {
      return {
        loading        : true,
        loadingChapters: true,
        navigation     : [],
        creatingChapter: false,
        newChapter     : '',
        studyData      : {
          id     : 0,
          title  : '',
          content: '',
        },
        config         : {
          key             : process.env.VUE_APP_FROALA_LICENCE,
          events          : {
            'froalaEditor.initialized': function () {
              console.log('initialized')
            }
          },
          inlineMode      : false,
          heightMin       : 100,
          heightMax       : 400,
          theme           : 'gray',
          charCounterCount: false,
          toolbarButtons  : [
            'bold',
            'italic',
            'underline',
            'strikeThrough',
            '|',
            'align',
            'formatOL',
            'formatUL',
          ]
        },
      }
    },
    mounted() {
      let params = {
        params: {
          context: 'edit',
        },
      };
      this.$store
        .dispatch('study/fetchStudy', {id: this.$route.params.study_id, params})
        .then(response => {
            this.$store
              .dispatch('study/fetchNavigation')
              .then(response => {
                this.navigation = response;
                this.loadingChapters = false;
              });

            this.loading = false;
            this.studyData = {
              id     : response.id,
              title  : response.title.raw,
              content: response.content.raw
            }
          }
        );

    },
    watch     : {},
    computed  : {
      ...mapState(['study', 'user']),
      ...mapGetters('user', ['currentUserCan']),
      ...mapGetters('study', ['getStudyNavigation']),
    },
    methods   : {
      getChapterLink(chapter) {
        return '/studio/studies/' + this.$route.params.study_id + '/' + chapter.id;
      },
      createChapter() {
        // only copy over the data
        let chapters = JSON.parse(JSON.stringify(this.navigation));

        chapters.push({
          'title': {
            'raw': this.newChapter,
          }
        });

        this.creatingChapter = true;
        this.$store
          .dispatch('study/updateNavigation', {studyID: this.studyData.id, data: chapters})
          .then(response => {
            this.navigation = response;
            this.creatingChapter = false;
            this.newChapter = '';
          })
      },
      saveStudy() {
        this.loading = true;
        this.$store
          .dispatch('study/updateStudy', {
            studyID: this.studyData.id, data: this.studyData
          })
          .then(response => {
            this.loading = false;
            this.studyData = {
              id     : response.id,
              title  : response.title.raw,
              content: response.content.raw
            };
          });
      },
      saveNavigation() {
        this.loadingChapters = true;
        this.$store
          .dispatch('study/updateNavigation', {studyID: this.studyData.id, data: this.navigation})
          .then(response => {
            this.navigation = response;
            this.loadingChapters = false;
          })
      }

    }
  }
</script>
<style scoped>

</style>
