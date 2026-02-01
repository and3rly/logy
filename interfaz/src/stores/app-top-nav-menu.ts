import { defineStore } from "pinia";

export const useAppTopNavMenuStore = defineStore({
  id: "appTopNavMenu",
  state: () => {
    return [
		{
      'url': '/',
      'icon': 'fa fa-home',
      'text': 'Home'
    }
	]}
});
