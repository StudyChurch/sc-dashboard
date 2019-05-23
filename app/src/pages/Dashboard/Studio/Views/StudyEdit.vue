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
				</card>
			</div>

			<div class="col-lg-4">
				<card>
					<h6>Details</h6>

					<form @submit.prevent="saveStudy">
						<div v-if="isOrgStudyAdmin">
							<p><strong>Status</strong></p>
							<el-radio-group v-model="studyData.status">
								<el-radio-button label="publish">Publish</el-radio-button>
								<el-radio-button label="private">Private</el-radio-button>
								<el-radio-button label="draft">Draft</el-radio-button>
								<el-radio-button label="future" disabled v-if="studyData.status === 'future'">Future</el-radio-button>
							</el-radio-group>
							<p class="card-category">
								<span v-if="studyData.status === 'publish'">This study will be available to other group leaders.</span>
								<span v-else-if="studyData.status === 'private'">This study will not be available to other group leaders.</span>
								<span v-else>This study is not yet published.</span>
							</p>
						</div>

						<p>
							<strong v-if="isPublished">Published: </strong>
							<strong v-else>Publish on: </strong>
							<el-date-picker v-model="studyData.date" type="date" placeholder="Pick a day"></el-date-picker>
						</p>

						<el-popover
							placement="top"
							width="230"
							v-model="deleteModal">
							<p>Are you sure you want to delete this study?</p>
							<div>
								<n-button size="sm" type="text" @click.native="deleteModal = false">cancel</n-button>
								<n-button type="danger" size="sm" @click.native="deleteStudy()">delete</n-button>
							</div>
							<n-button slot="reference" type="danger" class="float-right">Delete</n-button>
						</el-popover>

						<n-button type="primary" @click.native="saveStudy" v-loading="loading">Save</n-button>

					</form>

				</card>

				<card v-loading="loadingChapters">
					<h6>Chapters</h6>

					<el-popover
							placement="right-end"
							width=""
							v-model="deleteChapterModal">
						<p>Are you sure you want to delete this chapter?</p>
						<div>
							<n-button size="sm" type="text" @click.native="deleteChapterModal = false">cancel</n-button>
							<n-button type="danger" size="sm" @click.native="deleteChapter( currentChapterItem )">delete</n-button>
						</div>

					</el-popover>

					<draggable v-model="navigation" :options="{draggable: '.item', handle : '.drag-item'}" @end="saveNavigation">
						<div v-for="item in navigation" :key="item.id" class="item">
							<p>

							<a @click="deleteChapterModal = true; currentChapterItem = item" class="remove float-right" href="#">
								<font-awesome-icon icon="times"></font-awesome-icon>
							</a>

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

				<card v-loading="uploadingThumb">
					<h6>Thumbnail</h6>

					<avatar-cropper
						ref="thumbnail"
						@uploading="handleUploading"
						@uploaded="handleUploaded"
						@completed="handleCompleted"
						@error="handlerError"
						:uploadHandler="uploadHandler"
						:cropperOptions="{aspectRatio: 16/9}"
						:labels="{ submit: 'save', cancel: 'cancel'}"
						trigger="#pick-thumbnail" />

					<p><img :src="this.study.study.thumbnail"></p>
					<n-button type="primary" simple="" id="pick-thumbnail">change thumbnail</n-button>

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
    ActivityForm,
  } from 'src/components';

  import draggable from 'vuedraggable';
  import Chapter from '../Components/Chapter';
  import { Select, Option, RadioGroup, RadioButton, DatePicker, Input } from 'element-ui';
  import { mapState, mapGetters } from 'vuex';
  import AvatarCropper from "vue-avatar-cropper";
  import swal from 'sweetalert2';

  export default {
    components: {
      Card,
      NTable,
      NProgress,
      AnimatedNumber,
      TimeLine,
      TimeLineItem,
      ActivityForm,
      'el-select'      : Select,
      'el-option'      : Option,
      'el-radio-button': RadioButton,
      'el-radio-group' : RadioGroup,
      [Input.name]     : Input,
      [DatePicker.name]: DatePicker,
      Chapter,
      draggable,
      'avatar-cropper' : AvatarCropper
    },
    data      : function () {
      return {
        deleteModal    : false,
		deleteChapterModal: false,
        loading        : true,
        loadingChapters: true,
        uploadingThumb : false,
        navigation     : [],
        creatingChapter: false,
        newChapter     : '',
		currentChapterItem: {},
        studyData      : {
          id     : 0,
          title  : '',
          content: '',
          status : '',
        },
        config         : {
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
            this.setStudyData(response);
          }
        );

    },
    watch     : {},
    computed  : {
      ...mapState(['study', 'user']),
      ...mapGetters('user', ['currentUserCan']),
      ...mapGetters('study', ['getStudyNavigation']),
      ...mapGetters('group', ['isOrgAdmin']),
      isOrgStudy() {
        if (undefined === this.study.study.organization) {
          return false;
        }

        return this.study.study.organization.length;
      },
      isOrgStudyAdmin() {
        if (!this.isOrgStudy) {
          return false;
        }

        for (let i = 0; i < this.isOrgStudy; i++) {
          if (this.isOrgAdmin(this.study.study.organization[i])) {
            return true;
          }
        }

        return false;
      },
      isPublished() {
        let date = new Date();
        let pubdate = new Date(this.studyData.date);

        return date > pubdate;
      }
    },
    methods   : {
      getChapterLink(chapter) {
        return '/studio/studies/' + this.$route.params.study_id + '/' + chapter.id;
      },
      setStudyData(data) {
        this.studyData = {
          id     : data.id,
          title  : data.title.raw,
          content: data.content.raw,
          status : data.status,
          date   : data.date_gmt,
        }
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
		deleteChapter( item ) {

            this.loadingChapters = true;
            this.$store.dispatch( 'study/deleteStudyChapter', item.id ).then( response => {
                this.$store
                .dispatch('study/updateNavigation', {studyID: this.studyData.id })
                .then(response => {
					this.navigation = response;
            		this.loadingChapters = false;
            		this.deleteChapterModal = false;
        		});
        	});
		},
      saveStudy() {
        this.loading = true;
        this.$store
          .dispatch('study/updateStudy', {
            studyID: this.studyData.id, data: this.studyData
          })
          .then(response => {
            this.loading = false;
            this.setStudyData(response);
          });
      },
      deleteStudy() {
        this.loading = true;
        this.$store
          .dispatch('study/deleteStudy', this.studyData.id)
          .then(response => {
            this.$store.dispatch('alert/add', {
              message: 'Taking you to your dashboard.',
              type   : 'success',
            });
            window.location = '/';
          })
		  .finally(() => this.loading = false);
      },
      saveNavigation() {
        this.loadingChapters = true;
        this.$store
          .dispatch('study/updateNavigation', {studyID: this.studyData.id, data: this.navigation})
          .then(response => {
            this.navigation = response;
            this.loadingChapters = false;
          })
      },
      uploadHandler(cropper) {
        cropper.getCroppedCanvas(this.outputOptions).toBlob((blob) => {
          let formData = new FormData();

          formData.append('file', blob, this.$refs.thumbnail.filename);
          formData.append('title', this.studyData.title + ' thumbnail');
          formData.append('action', 'wp_handle_upload');

          this.uploadingThumb = true;
          this.$store
            .dispatch('study/updateStudyThumbnail', {studyID: this.studyData.id, data: formData})
            .then((response) => {
              this.uploadingThumb = false;
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
    }
  }
</script>
<style scoped>

	.remove {
		color: #FF3636;
		margin-left: 7px;
	}

</style>
