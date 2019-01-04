<template>

	<div class="sc-group--studies">
		<div class="text-right col-md-12" v-if="isGroupAdmin()">
			<n-button type="primary"  @click.native="getStudies(); showModal = true">Add Study</n-button>
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
					<template slot-scope="scope">
						<span style="word-break:break-word;" v-html="scope.row.title.rendered"></span></template>
				</el-table-column>

				<el-table-column
					label="Actions"
					width="75">
					<template slot-scope="scope">
						<n-button @click.native="addStudy(scope.row.id)"
								  class="add"
								  type="primary"
								  :disabled="getStudyIDs().includes(scope.row.id.toString()) || getStudyIDs().includes(scope.row.id)"
								  size="sm" round icon>
							<i class="fa fa-plus"></i>
						</n-button>
					</template>
				</el-table-column>

			</el-table>

		</modal>

		<div class="row" v-loading="loading">
			<div class="col-12">
				<card card-body-classes="table-full-width" no-footer-line>
					<div>

						<el-table stripe
								  style="width: 100%;"
								  :show-header="false"
								  :data="groupStudies">

							<el-table-column type="expand">
								<template slot-scope="props">
									<div v-html="props.row.description"></div>
								</template>
							</el-table-column>

							<el-table-column width="220px">
								<div slot-scope="{row}" class="img-container">
									<router-link :to="'/groups/' + groupData.slug + $root.cleanLink(row.link)">
										<img :src="row.thumbnail" /></router-link>
								</div>
							</el-table-column>

							<el-table-column min-width="220" key="title" label="Title">
								<template slot-scope="{row}">
									<p>
										<router-link :to="'/groups/' + groupData.slug + $root.cleanLink(row.link)">{{ row.title | decode }}</router-link>
									</p>
								</template>
							</el-table-column>

							<el-table-column
								v-if="isGroupAdmin()"
								fixed="right"
								label="Actions"
								width="110">
								<template slot-scope="scope">
									<n-button @click.native="removeStudy(scope.row.id)"
											  class="remove"
											  type="danger"
											  size="sm" round icon>
										<i class="fa fa-times"></i>
									</n-button>
								</template>
							</el-table-column>
						</el-table>
					</div>
				</card>
			</div>
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
  import { mapState, mapGetters } from 'vuex';

  function getDefaultData () {
    return {
      pagination    : {
        perPage    : 25,
        currentPage: 1,
        total      : 0
      },
      loading       : false,
      searchQuery   : '',
      creatingTodo  : false,
      showModal     : false,
      loadingStudies: true,
      loadingMore   : false,
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
    computed  : {
      ...mapState(['user', 'group']),
      ...mapGetters('user', ['getUserById']),
      ...mapGetters('group', ['isGroupAdmin', 'isGroupAdmin', 'getGroupMembers', 'getGroupAdmins']),
      groupStudies() {
        return this.group.group.studies;
      }
    },
    methods   : {
      getStudyIDs() {
        return this.groupStudies.map(study => study.id);
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
        this.loading = true;

        this.$store
          .dispatch('group/update', {groupID: this.group.group.id, data: {studies}})
          .then((response) => {
            this.loading = false;
          });
      },
      removeStudy(id) {
        let studies = this.groupStudies.map(study => study.id);
        let index = studies.indexOf(id);

        if (index > -1) {
          studies.splice(index, 1);
        } else {
          return;
        }

        this.loading = true;

        this.$store
          .dispatch('group/update', {groupID: this.group.group.id, data: {studies}})
          .then(() => {
            this.loading = false;
          });
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