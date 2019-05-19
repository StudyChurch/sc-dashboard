<template>

	<div class="sc-group--assignments" v-loading="loadingTodos" style="min-height: 200px;">

		<div class="text-right" v-if="isGroupAdmin()">
			<n-button type="primary" @click.native="action = 'Create'; getStudies(); showModal = true">Create To-Do</n-button>
		</div>
		<modal :show.sync="showModal" headerclasses="justify-content-center" v-loading="creatingTodo">
			<h4 slot="header" class="title title-up">{{ action }} To-Do</h4>

			<div v-for="study in currentTodo.studies">
				<label :for="'study-' + study.id" v-html="study.title.rendered"></label>
				<p>
					<el-select v-model="study.value" :id="'study-' + study.id" multiple placeholder="Select" class="select-primary">
						<el-option
							class="select-primary"
							v-for="chapter in study.navigation"
							:key="chapter.id"
							:label="chapter.title.rendered"
							:value="chapter.id">
						</el-option>
					</el-select>
				</p>
			</div>

			<p>
				<label for="instructions">Instructions</label>
				<el-input
					ref="description"
					type="textarea"
					id="instructions"
					:autosize="{ minRows: 4 }"
					resize="none"
					label="Study Description"
					v-model="currentTodo.content"></el-input>
			</p>

			<p>
				<label for="datepicker">Due Date</label>
				<fg-input>
					<el-date-picker id="datepicker" value-format="yyyy-MM-dd" v-model="currentTodo.date" type="date" placeholder="Pick a day">
					</el-date-picker>
				</fg-input>
			</p>

			<template slot="footer">
				<n-button type="primary" @click.native="createTodo">{{ action }}</n-button>
			</template>
		</modal>

		<card v-for="data in todoData" :class="'card todo'">
			&nbsp;
			<h6>Due Date: {{data.date}}</h6>
			<p v-for="lesson in data.lessons">
				<router-link :to="'/groups/' + $route.params.slug + $root.cleanLink(lesson.link)">
					<i class="now-ui-icons design_bullet-list-67"></i>&nbsp;
					<span v-html="lesson.title"></span></router-link>
			</p>
			<p v-html="data.content"></p>
			<p class="todo-actions">
				<n-button type="info"
						  @click.native="editTodo( data.key )"
						  size="sm"
						  class="remove btn-neutral"
						  icon
						  v-if="isGroupAdmin()"><font-awesome-icon icon="edit"></font-awesome-icon>
				</n-button>
				<n-button type="danger"
						  @click.native="removeTodo( data.key )"
						  size="sm"
						  class="remove btn-neutral"
						  icon
						  v-if="isGroupAdmin()"><font-awesome-icon icon="times"></font-awesome-icon>
				</n-button>
			</p>
		</card>

		<p v-if="!todoData.length && !loadingTodos" class="text-center">There are no upcoming to-dos.</p>

	</div>

</template>
<script>
  import { Input, Message, Select, Option, DatePicker } from 'element-ui';
  import { mapState, mapGetters } from 'vuex';
  import swal from 'sweetalert2'

  import {
    Card,
    Table as NTable,
    Button,
    Modal,
  } from 'src/components'

  function getDefaultData () {
    return {
      creatingTodo: false,
      showModal   : false,
      loadingTodos: true,
      loadingMore : false,
      todoData    : [],
      todoPage    : 1,
      newTodo     : {
        content: '',
        studies    : [],
        date       : ''
      },

		currentTodo: {
          content: '',
			studies: [],
			date: '',
		},
		action: 'Create',
    }
  }

  export default {
    components: {
      Card,
      NTable,
      Button,
      Modal,
      'el-select'     : Select,
      'el-option'     : Option,
      'el-date-picker': DatePicker

    },
    props     : {
      groupData: {
        default: {
          id     : 0,
          studies: []
        }
      },
    },
    data      : getDefaultData,
    mounted() {
      this.getGroupTodos();
    },
    computed  : {
      ...mapState(['user', 'group', 'assignment']),
      ...mapGetters('user', ['getUserById']),
      ...mapGetters('group', ['isGroupAdmin', 'isGroupAdmin', 'getGroupMembers', 'getGroupAdmins']),

      leaders() {
        return this.groupData.members.filter(member => member.admin);
      },
      members() {
        return this.groupData.members.filter(member => !member.admin);
      }
    },
    methods   : {
      createTodo() {
        if (!this.currentTodo.date || !this.currentTodo.content) {
          Message.error('Please enter a date and description for your new to-do item');
          return;
        }

        this.creatingTodo = true;
        let studies = [];
        for (let i = 0; i < this.currentTodo.studies.length; i++) {
          studies = studies.concat(this.currentTodo.studies[i].value);
        }

        if ( 'Edit' === this.action ) {

            this.currentTodo.group_id = this.groupData.id;
            this.currentTodo.lessons = studies;

            this.$store.dispatch( 'assignment/updateAssignment', this.currentTodo ).then( response => {
				console.log( 'Edit saved', response );
				this.creatingTodo = false;
				this.getGroupTodos();
		  } );
		} else {
            this.$store.dispatch( 'assignment/createAssignment', {
                group_id: this.groupData.id,
                content : this.currentTodo.content,
                lessons : studies,
                date    : this.currentTodo.date,
            } ).then( response => {
                this.getGroupTodos();
           	 	this.creatingTodo = false;
			} );
		}
      },
		removeTodo( itemId ) {

         this.loadingTodos = true;

         swal( {
			 title: 'Are you sure you want to remove this?',
			 text: 'You won\'t be able to revert this.',
			 type: 'warning',
			 showCancelButton: true,
			 confirmButtonText: 'Remove',
			 showLoaderOnConfirm: true,
			 preConfirm: () => {

			    this.$store.dispatch( 'assignment/deleteAssignment', itemId ).then( response => {

					// Works but I think this should just be part of the Service response?
					if ( response.message.length ) {

						if ( response.success ) {
							Message.success( response.message );
						} else {
							Message.error( response.message );
						}

						this.getGroupTodos();
					} else {
						Message.error( 'An error occurred.' );
						this.loadingTodos = false;
					}
        		} );
		 	}
		 } );
		},
		editTodo( itemId ) {

          console.log( 'itemId', itemId );

          this.$store.dispatch( 'assignment/getAssignment', itemId ).then( response => {

              this.action = 'Edit';

              console.log( 'Response', response );

              this.currentTodo.content = response.content;
              this.currentTodo.date = new Date( response.date );
              this.currentTodo.id = itemId;

              this.getStudies();

             console.log( 'current TODO', this.currentTodo );

            //this.getStudyById( response.lessons[0] );

             this.showModal = true;
		  } );

		},
      getStudies () {

          if ( 'Create' === this.action ) {
              this.currentTodo = this.newTodo;
          }

          console.log( 'Group Data', this.groupData );

        if ( this.currentTodo.studies && this.currentTodo.studies.length) {
          return;
        }

        for (let i = 0; i < this.groupData.studies.length; i++) {
          this.getStudy(this.groupData.studies[i]);
        }

        console.log( 'GET STUDIES', this.currentTodo );
      },
      getStudy (study) {
        this.$http
          .get('/wp-json/studychurch/v1/studies/' + study.id + '/navigation')
          .then(response => {
            study.navigation = response.data;

            if ( ! this.currentTodo.studies ) {
                this.currentTodo.studies = [];
			}

            this.currentTodo.studies.push(study);

            console.log( 'Push Study', this.currentTodo );
          })
      },
      getGroupTodos () {
        this.loadingTodos = true;

        this.$store.dispatch( 'assignment/fetchAssignments' ).then( response => {
           //console.log( 'response', response );
			this.todoData = response;
			this.showModal = false;
		} ).finally( () => this.loadingTodos = false );
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

	.todo-actions {
		position: absolute;
		right: 0;
		top: 0;
		display: block;
	}
</style>