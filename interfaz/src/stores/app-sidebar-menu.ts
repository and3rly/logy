import { defineStore } from "pinia";

export const useAppSidebarMenuStore = defineStore({
  id: "appSidebarMenu",
  state: () => {
    return [
    {
			text: 'Navigation',
			is_header: true
		},
		{
      'url': '/',
      'icon': 'fa fa-home',
      'text': 'Home'
    }
	]}
});
