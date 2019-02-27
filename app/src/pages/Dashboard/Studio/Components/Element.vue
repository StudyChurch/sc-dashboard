<template>
	<div class="sc-studio--element" v-loading="loading">
		<div v-if="!editing" class="card-body">
			<div class="sc-studio--element--actions float-right">
				<drop-down position="right" class="float-left">
					<n-button slot="title" class="dropdown-toggle no-caret" type="neutral" icon>
						<font-awesome-icon icon="cogs"></font-awesome-icon>
					</n-button>

					<a class="dropdown-item" href="#" @click.prevent="editing=true">Edit</a>
					<a class="dropdown-item text-danger" href="#" @click.prevent="removeItem">Delete</a>
				</drop-down>
				<n-button slot="title" type="neutral" class="drag-item" icon>
					<font-awesome-icon icon="arrows-alt"></font-awesome-icon>
				</n-button>
			</div>

			<div v-html="item.content.rendered"></div>

			<div v-if="item.data_type === 'question_short' ||  item.data_type === 'question_long'">
				<div class="sc-answer">
					<activity-form
						ref="answerForm"
						:disabled="true"
						elClass="sc-activity--answer"
						component="groups"
						type="answer_update"
						placeholder="This study is in preview mode."
						:primaryItem="0"></activity-form>
				</div>
			</div>
		</div>
		<card v-else>
			<p v-if="0">
				<el-input type="text"></el-input>
			</p>
			<froala tag="textarea" :config="config" v-model="model.content"></froala>

			<div style="margin-top: 20px">
				<el-checkbox v-model="model.isQuestion" label="Show a response field" border></el-checkbox>
			</div>

			<div style="margin-top: 20px" v-if="model.isQuestion">
				<el-checkbox v-model="model.isPrivate" label="Make response private (don't share it with the group)" border></el-checkbox>
			</div>

			<n-button type="danger" class="float-right" @click.native="removeItem">Delete</n-button>
			<n-button type="primary" @click.native="save">Save</n-button>
			<n-button type="default" @click.native="cancelEdit">Cancel</n-button>
		</card>
	</div>
</template>
<script>
  import { mapState, mapGetters } from 'vuex';
  import { ActivityForm } from 'src/components';
  import { Checkbox } from 'element-ui';

  export default {
    components: {ActivityForm, 'el-checkbox': Checkbox},
    data      : function () {
      return {
        editing: false,
        loading: false,
        config : {
          events          : {
            'froalaEditor.initialized': function () {
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
        model  : {
          content   : '',
          isQuestion: false,
          isPrivate : false,
        },
      }
    },
    props     : {
      item: {
        default: {
          content: {
            rendered: '',
          }
        }
      }
    },
    watch     : {},
    computed  : {
      ...mapState(['study', 'user']),
      ...mapGetters('user', ['currentUserCan']),
      isQuestion() {
        return this.item.data_type === 'question_short' || this.item.data_type === 'question_long';
      }
    },
    methods   : {
      removeItem() {
        this.loading = true;
        this.$store
          .dispatch('study/updateStudyChapter', {
            chapterID: this.study.chapter.id,
            data     : {elements: this.study.chapter.elements.filter(element => element.id !== this.item.id)}
          })
          .then(response => this.loading = false);
      },
      cancelEdit() {
        if (!this.item.content.raw) {
          this.removeItem();
        } else {
          this.editing = false;
          this.setup();
        }
      },
      save() {
        this.item.content.raw = this.model.content;
        this.item.data_type = this.model.isQuestion ? 'question_short' : 'content';
        this.item.is_private = this.model.isPrivate;

        this.loading = true;
        this.$store
          .dispatch('study/updateStudyChapter',
            {chapterID: this.study.chapter.id, data: {elements: this.study.chapter.elements}})
          .then(response => {
            this.loading = false;
            this.editing = false;
            this.setup();
          });
      },
      setup() {
        this.model = {
          content   : this.item.content.raw,
          isQuestion: this.isQuestion,
          isPrivate : this.item.is_private,
        }
      }
    },
    mounted() {
      this.setup();
      this.editing = undefined === this.item.editing ? false : this.item.editing;
    }
  }
</script>
<style scoped>

</style>
