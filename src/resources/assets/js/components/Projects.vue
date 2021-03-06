<template>
    <div>
        <div class="card bg-inverse card-projects">
            <div class="card-block text-center">
                <div class="row">
                    <div class="col project-title">
                        Projects
                    </div>
                </div>

                <div class="row">
                    <div class="col-6">
                        <div class="input-group search-group">
                            <input
                                v-model="filter"
                                class="form-control form-control-sm search-project"
                                placeholder="filter"
                            >
                            <div v-if="filter" @click="resetFilter()" class="input-group-addon search-addon">
                                <i class="fa fa-trash"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 text-right">
                        <div class="btn btn-info" @click="toggleAll()">
                            toggle
                        </div>
                        <div class="btn btn-danger" @click="runAll()">
                            run
                        </div>
                        <div class="btn btn-warning" @click="reset()">
                            reset
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="filteredProjects.length == 0" class="container d-flex align-items-center justify-content-center" style="height: 500px;">
            <div class="row justify-content-center">
                <div class="col-12">
                    <h1 class="card-title text-center">NO PROJECTS FOUND</h1>
                </div>
            </div>
        </div>

        <div class="list-group" v-if="filteredProjects.length > 0">
            <div class="card-projects-items">
                <div class="row">
                    <div class="col-md-12">
                        <ul class="list-group">
                            <li
                                v-for="project in filteredProjects"
                                :class="'list-group-item ' + (! project.enabled ? 'dim ' : '') + (selectedProject.id == project.id ? 'active ' : '')"
                                @click="changeProject(project)"
                            >
                                <div class="row">
                                    <div class="col">
                                        <input
                                            type="checkbox"
                                            class="project-checkbox testCheckbox"
                                            @click="toggleProject(project)"
                                            :checked="project.enabled"
                                        />

                                        {{ project.name }}
                                    </div>
                                    <div class="col-2 text-right">
                                        <span v-if="project.state == 'running'">
                                            <i class="fa fa-cog fa-spin fa-fw"></i>
                                        </span>

                                        <span v-if="project.state == 'failed'" class="project-state text-danger">
                                            <i @click="run(project)" class="fa fa-times cursor-pointer"></i>
                                        </span>

                                        <span v-if="project.state == 'ok'" class="project-state text-success">
                                            <i @click="run(project)" class="fa fa-check cursor-pointer"></i>
                                        </span>

                                        <span v-if="project.state == 'queued'" class="project-state text-warning">
                                            <i @click="run(project)" class="fa fa-clock-o cursor-pointer"></i>
                                        </span>

                                        <span v-if="project.state == 'idle'" class="project-state text-default pale">
                                            <i @click="run(project)" class="fa fa-pause cursor-pointer"></i>
                                        </span>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import {mapState} from 'vuex';
    import {mapMutations} from 'vuex'
    import {mapActions} from 'vuex'
    import {mapGetters} from 'vuex'

    export default {
        computed: {
            ...mapState([
                'laravel',
                'projects',
                'selectedPanel',
                'filters',
            ]),

            ...mapGetters([
                'selectedProject',
                'filteredProjects',
                'filteredProjectsIds',
            ]),

            filter: {
                get () {
                    return this.$store.state.filters.projects
                },
                set (value) {
                    this.$store.dispatch('setProjectsFilter', value)
                }
            }
        },

        methods: {
            ...mapActions(['loadData', 'setSelectedProjectId']),

            changeProject(project) {
                if (this.selectedProject != project) {
                    this.setSelectedProjectId(project.id);

                    this.$store.commit('setSelectedPanel', 'log');

                    this.$store.commit('setWasRunning', false);
                }
            },

            toggleProject(project) {
                axios.get(this.laravel.routes.prefixes.projects+'/'+project.id+'/enable/'+!project.enabled)
                    .then(response => this.loadData());
            },

            resetFilter() {
                this.$store.commit('setProjectsFilter', '')
            },

            run(project) {
                axios.post(this.laravel.routes.prefixes.projects+'/run', { projects: project.id });
            },

            runAll() {
                axios.post(this.laravel.routes.prefixes.projects+'/run', { projects: this.filteredProjectsIds });
            },

            reset() {
                axios.post(this.laravel.routes.prefixes.projects+'/reset/', { projects: this.filteredProjectsIds });
            },

            toggleAll() {
                axios.get(this.laravel.routes.prefixes.projects+'/toggle-all')
                    .then(response => this.loadData());
            },
        }
    }
</script>
