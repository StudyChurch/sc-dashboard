<template>
	<div class="sc-studio--study-edit" v-loading="loading">
		<div class="container-fluid">
			<div class="row d-flex justify-content-center">
				<div class="col-lg-10">
					<card style="min-height: 200px;">
						<h6 slot="header" class="card-title">Chapter Details</h6>
						<p>
							<el-input type="text" v-model="model.title" style="font-size:22px;"></el-input>
						</p>

						<froala tag="textarea" :config="config" v-model="model.content"></froala>

						<p>
							<n-button type="primary" @click.native="save">Save</n-button>
						</p>

						<hr />

						<h6>Study Elements</h6>

						<draggable v-model="elements" :options="{draggable: '.item', handle : '.drag-item'}" @end="save">
							<div v-for="(element, index) in elements" :key="element.id" class="item">
								<div class="add-item" @click="addItem(index)"><span>add item</span></div>
								<Element :item="element" @saveChapter="save"></Element>
							</div>
						</draggable>
						<p>
							<n-button type="primary" class="float-right" @click.native="addItem(elements.length)">Add Item</n-button>
						</p>
					</card>
				</div>
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
  import Element from '../Components/Element';
  import { Select, Option } from 'element-ui';
  import { mapState, mapGetters } from 'vuex';

  export default {
    components: {
      Card,
      NTable,
      NProgress,
      AnimatedNumber,
      TimeLine,
      TimeLineItem,
      ActivityForm,
      'el-select': Select,
      'el-option': Option,
      Element,
      draggable
    },
    data      : function () {
      return {
        items   : [],
        chapters: [],
        model   : {
          title  : '',
          content: '',
        },
        chapter : {
          id      : 0,
          title   : {
            rendered: '',
            raw     : '',
          },
          excerpt : {
            rendered: '',
            raw     : '',
          },
          elements: [],
        },
        loading : true,
        config  : {
          key             : process.env.VUE_APP_FROALA_LICENCE,
          events          : {
            'froalaEditor.initialized': function () {
            }
          },
          inlineMode      : false,
          heightMin       : 100,
          heightMax       : 400,
          theme           : 'gray',
          charCounterCount: false,
		  placeholderText: 'Chapter introduction, video, etc...',
          toolbarButtons  : [
            'bold',
            'italic',
            'underline',
            'strikeThrough',
            '|',
            'paragraphFormat',
            'align',
            'formatOL',
            'formatUL',
            'quote',
            '|',
            'insertLink',
            'insertImage',
            'insertTable',
            '|',
            'clearFormatting',
            'undo',
            'redo',
            'fullscreen',
          ]
        },
      }
    },
    mounted() {
      this.$store
        .dispatch('study/getStudyChapter', {study: this.$route.params.study_id, chapter: this.$route.params.chapter_id})
        .then(response => {
          this.chapter = response;
          this.loading = false;
          this.model = {
            title  : response.title.raw,
            content: response.content.raw,
          };
        });
    },
    watch     : {},
    methods   : {
      addItem(index) {
        console.log('index: ' + index);
        let newElements = this.elements;
        newElements.splice(index, 0, {
          id     : Date.now(),
          editing: true,
          title  : {
            raw: '',
          },
          content: {
            raw: '',
          }
        });

        this.elements = newElements;
      },
      save() {
        this.loading = true;
        this.$store
          .dispatch('study/updateStudyChapter', {
            chapterID: this.study.chapter.id, data: {
              elements: this.elements,
              title   : this.model.title,
              content : this.model.content,
            }
          })
          .then(response => {
            this.loading = false;
            this.model = {
              title  : response.title.raw,
              content: response.content.raw,
            };
          });
      }
    },
    computed  : {
      ...mapState(['study', 'user']),
      ...mapGetters('user', ['currentUserCan']),
      elements: {
        get() {
          return this.study.chapter.elements;
        },
        set(value) {
          this.study.chapter.elements = value;
        }
      }
    }
  }
</script>
<style scoped lang="scss">

</style>
