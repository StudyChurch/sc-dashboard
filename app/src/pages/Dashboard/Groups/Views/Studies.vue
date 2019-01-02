<template>

	<div class="sc-group--studies row">
		<div class="text-right col-md-12">
			<n-button type="primary" @click.native="getStudies(); showModal = true">Add Study</n-button>
		</div>

		<modal :show.sync="showModal" headerclasses="justify-content-center">
			<h4 slot="header" class="title title-up">Add a study</h4>
			<el-table
				:data="studies"
				style="width:100%"
				:show-header="false"
				height="400"
				v-loading="loadingStudies"
			>
				<el-table-column
					label="Thumb"
					width="80">

					<template slot-scope="scope">
						<img
							v-if="undefined !== scope.row._embedded['wp:featuredmedia']"
							:src="scope.row._embedded['wp:featuredmedia'][0].media_details.sizes.medium.source_url" />
					</template>

				</el-table-column>

				<el-table-column
					label="Title">
					<template slot-scope="scope"><span v-html="scope.row.title.rendered"></span></template>
				</el-table-column>

				<el-table-column
					label="Actions"
					width="110">
					<template slot-scope="scope">
						<n-button v-if="!getStudyIDs().includes(scope.row.id.toString()) && !getStudyIDs().includes(scope.row.id)" size="sm" type="primary" @click.native="addStudy(scope.row.id)">Select</n-button>
						<span v-else>Selected</span>
					</template>
				</el-table-column>

			</el-table>

		</modal>

		<div v-for="study in groupStudies" class="col-md-6 d-block">
			<study-card
				v-if="study.id"
				:id="study.id"
				:description="study.description"
				:title="study.title"
				:thumbnail="study.thumbnail || ''"
				:link="'/groups/' + groupData.slug + $root.cleanLink(study.link)"></study-card>
		</div>

	</div>

</template>
<script>
  import {
    StudyCard,
    Table as NTable,
    Button,
    Modal
  } from 'src/components'

  import { Table, TableColumn } from 'element-ui';
  import NButton from '../../../../components/Button';

  function getDefaultData () {
    return {
      creatingTodo  : false,
      showModal     : false,
      loadingStudies: true,
      loadingMore   : false,
      groupStudies  : [],
      studies       : [],
      todoData      : [],
      todoPage      : 1,
    }
  }

  export default {
    components: {
      NButton,
      StudyCard,
      NTable,
      Button,
      Modal,
      'el-table'       : Table,
      'el-table-column': TableColumn
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
    watch     : {
      'groupData' (to, from) {
        this.groupStudies = this.groupData.studies;
      }
    },
    methods   : {
      getStudyIDs() {
        return this.groupStudies.map(study => study.id);
      },
      createTodo() {
        if (!this.newTodo.name || !this.newTodo.description) {
          Message.error('Please enter a name and description for your new group');
          return;
        }

        this.creatingTodo = true;

        this.$http.post('/wp-json/studychurch/v1/groups/', {
          name       : this.newTodo.name,
          description: this.newTodo.description,
          user_id    : this.$root.userData.id,
          status     : 'hidden',
        })
          .then(response => {
            this.creatingTodo = false;
            Message.success('Success! Taking your new group ...');
          })

      },
      getStudies() {
        if (this.studies.length) {
          return;
        }

        this.loadingStudies = true;
        this.$http
          .get('/wp-json/studychurch/v1/studies/?per_page=100&status=publish,private&_embed=true')
          .then(response => {
            this.studies = response.data;
          })
          .finally(() => this.loadingStudies = false)
      },
      addStudy(id) {
        let studies = this.groupStudies.map(study => study.id);
        studies.push(id);
        this.loadingStudies = true;

        this.$http
          .post('/wp-json/studychurch/v1/groups/' + this.groupData.id, {
            studies: studies
          })
          .then(response => {
            this.groupStudies = response.data[0].studies;
          })
          .finally(() => this.loadingStudies = false)
      },
      reset (keep) {
        let def = getDefaultData();
        def[keep] = this[keep];
        Object.assign(this.$data, def);
      }
    },
    mounted() {
      this.groupStudies = this.groupData.studies;
    }
  }
</script>
<style>
</style>