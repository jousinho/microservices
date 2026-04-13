import { createRouter, createWebHashHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import GameView from '../views/GameView.vue'
import ScoreboardView from '../views/ScoreboardView.vue'

export default createRouter({
  history: createWebHashHistory(),
  routes: [
    { path: '/',           component: HomeView },
    { path: '/game',       component: GameView },
    { path: '/scoreboard', component: ScoreboardView },
  ],
})
