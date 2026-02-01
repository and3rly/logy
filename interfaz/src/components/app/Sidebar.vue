<script setup lang="ts">
import { useAppSidebarMenuStore } from '@/stores/app-sidebar-menu';
import { useAppOptionStore } from '@/stores/app-option';
import { onMounted } from 'vue';
import { slideToggle } from '@/composables/slideToggle.js';
import { slideUp } from '@/composables/slideUp.js';
import { slideDown } from '@/composables/slideDown.js';
import SidebarNav from '@/components/app/SidebarNav.vue';

const appSidebarMenu = useAppSidebarMenuStore();
const appOption = useAppOptionStore();
var appSidebarFloatSubmenuTimeout = '';
var appSidebarFloatSubmenuDom = '';

function appSidebarMobileToggled() {
	appOption.appSidebarMobileToggled = !appOption.appSidebarMobileToggled;
}

function handleSidebarMinifyFloatMenuClick() {
	var elms = [].slice.call(document.querySelectorAll('.app-float-submenu .menu-item.has-sub > .menu-link'));
	if (elms) {
		elms.map(function(elm) {
			elm.onclick = function(e) {
				e.preventDefault();
				var targetItem = this.closest('.menu-item');
				var target = targetItem.querySelector('.menu-submenu');
				var targetStyle = getComputedStyle(target);
				var close = (targetStyle.getPropertyValue('display') != 'none') ? true : false;
				var expand = (targetStyle.getPropertyValue('display') != 'none') ? false : true;
				
				slideToggle(target);
				
				var loopHeight = setInterval(function() {
					var targetMenu = document.querySelector('.app-float-submenu');
					var targetHeight = targetMenu.clientHeight;
					var targetOffset = targetMenu.getBoundingClientRect();
					var targetOriTop = targetMenu.getAttribute('data-offset-top');
					var targetMenuTop = targetMenu.getAttribute('data-menu-offset-top');
					var targetTop 	 = targetOffset.top;
					var windowHeight = document.body.clientHeight;
					if (close) {
						if (targetTop > targetOriTop) {
							targetTop = (targetTop > targetOriTop) ? targetOriTop : targetTop;
							targetMenu.style.top = targetTop + 'px';
							targetMenu.style.bottom = 'auto';
						}
					}
					if (expand) {
						if ((windowHeight - targetTop) < targetHeight) {
							var arrowBottom = (windowHeight - targetMenuTop) - 22;
							targetMenu.style.top = 'auto';
							targetMenu.style.bottom = 0;
						}
						var floatSubmenuElm = document.querySelector('.app-float-submenu');
						if (targetHeight > windowHeight) {
							if (floatSubmenuElm) {
								var splitClass = ('overflow-scroll mh-100vh').split(' ');
								for (var i = 0; i < splitClass.length; i++) {
									floatSubmenuElm.classList.add(splitClass[i]);
								}
							}
						}
					}
				}, 1);
				setTimeout(function() {
					clearInterval(loopHeight);
				}, 250);
			}
		});
	}
}

function handleSidebarMinifyFloatMenu() {
	var elms = [].slice.call(document.querySelectorAll('.app-sidebar .menu > .menu-item.has-sub > .menu-link'));
	if (elms) {
		elms.map(function(elm) {
			elm.onmouseenter = function() {
				var appElm = document.querySelector('.app');
				if (appElm && appElm.classList.contains('app-sidebar-minified')) {
					clearTimeout(appSidebarFloatSubmenuTimeout);
					var targetMenu = this.closest('.menu-item').querySelector('.menu-submenu');
					if (appSidebarFloatSubmenuDom == this && document.querySelector('.app-float-submenu')) {
						return;
					} else {
						appSidebarFloatSubmenuDom = this;
					}
					var targetMenuHtml = targetMenu.innerHTML;
					if (targetMenuHtml) {
						var bodyStyle     = getComputedStyle(document.body);
						var sidebarOffset = document.querySelector('.app-sidebar').getBoundingClientRect();
						var sidebarWidth  = parseInt(document.querySelector('.app-sidebar').clientWidth);
						var sidebarX      = (bodyStyle.getPropertyValue('direction') != 'rtl') ? (sidebarOffset.left + sidebarWidth) : (document.body.clientWidth - sidebarOffset.left);
						var targetHeight  = handleGetHiddenMenuHeight(targetMenu);
						var targetOffset  = this.getBoundingClientRect();
						var targetTop     = targetOffset.top;
						var targetLeft    = (bodyStyle.getPropertyValue('direction') != 'rtl') ? sidebarX : 'auto';
						var targetRight   = (bodyStyle.getPropertyValue('direction') != 'rtl') ? 'auto' : sidebarX;
						var windowHeight  = document.body.clientHeight;
						
						if (!document.querySelector('.app-float-submenu')) {
							var overflowClass = '';
							if (targetHeight > windowHeight) {
								overflowClass = 'overflow-scroll mh-100vh';
							}
							var html = document.createElement('div');
							html.setAttribute('id', 'app-float-submenu');
							html.setAttribute('class', 'app-float-submenu '+ overflowClass);
							html.setAttribute('data-offset-top', targetTop);
							html.setAttribute('data-menu-offset-top', targetTop);
							html.innerHTML = targetMenuHtml;
							appElm.appendChild(html);
							
							var elm = document.getElementById('app-float-submenu');
							elm.onmouseover = function() {
								clearTimeout(appSidebarFloatSubmenuTimeout);
							};
							elm.onmouseout = function() {
								appSidebarFloatSubmenuTimeout = setTimeout(() => {
									document.querySelector('.app-float-submenu').remove();
								}, 250);
							};
						} else {
							var floatSubmenu = document.querySelector('.app-float-submenu');
							var floatSubmenuElm = document.querySelector('.app-float-submenu');
							
							if (targetHeight > windowHeight) {
								if (floatSubmenuElm) {
									var splitClass = ('overflow-scroll mh-100vh').split(' ');
									for (var i = 0; i < splitClass.length; i++) {
										floatSubmenuElm.classList.add(splitClass[i]);
									}
								}
							}
							floatSubmenu.setAttribute('data-offset-top', targetTop);
							floatSubmenu.setAttribute('data-menu-offset-top', targetTop);
							floatSubmenuElm.innerHTML = targetMenuHtml;
						}
				
						var targetHeight = document.querySelector('.app-float-submenu').clientHeight;
						var floatSubmenuElm = document.querySelector('.app-float-submenu');
						if ((windowHeight - targetTop) > targetHeight) {
							if (floatSubmenuElm) {
								floatSubmenuElm.style.top = targetTop + 'px';
								floatSubmenuElm.style.left = targetLeft + 'px';
								floatSubmenuElm.style.bottom = 'auto';
								floatSubmenuElm.style.right = targetRight + 'px';
							}
						} else {
							var arrowBottom = (windowHeight - targetTop) - 21;
							if (floatSubmenuElm) {
								floatSubmenuElm.style.top = 'auto';
								floatSubmenuElm.style.left = targetLeft + 'px';
								floatSubmenuElm.style.bottom = 0;
								floatSubmenuElm.style.right = targetRight + 'px';
							}
						}
						handleSidebarMinifyFloatMenuClick();
					} else {
						appSidebarFloatSubmenuDom = '';
						document.querySelector('.app-float-submenu').remove();
					}
				}
			}
			elm.onmouseleave = function() {
				var elm = document.querySelector('.app');
				if (elm && elm.classList.contains('app-sidebar-minified')) {
					appSidebarFloatSubmenuTimeout = setTimeout(() => {
						appSidebarFloatSubmenuDom = '';
						document.querySelector('.app-float-submenu').remove();
					}, 250);
				}
			}
		});
	}
};

function handleGetHiddenMenuHeight(elm) {
	elm.setAttribute('style', 'position: absolute; visibility: hidden; display: block !important');
	var targetHeight  = elm.clientHeight;
	elm.removeAttribute('style');
	return targetHeight;
}

onMounted(() => {
	var handleSidebarMenuToggle = function(menus, expandTime) {
		menus.map(function(menu) {
			menu.onclick = function(e) {
				e.preventDefault();
				var target = this.nextElementSibling;
	
				menus.map(function(m) {
					var otherTarget = m.nextElementSibling;
					if (otherTarget !== target) {
						slideUp(otherTarget, expandTime);
						otherTarget.closest('.menu-item').classList.remove('expand');
						otherTarget.closest('.menu-item').classList.add('closed');
					}
				});
	
				var targetItemElm = target.closest('.menu-item');
			
				if (targetItemElm.classList.contains('expand') || (targetItemElm.classList.contains('active') && !target.style.display)) {
					targetItemElm.classList.remove('expand');
					targetItemElm.classList.add('closed');
					slideToggle(target, expandTime);
				} else {
					targetItemElm.classList.add('expand');
					targetItemElm.classList.remove('closed');
					slideToggle(target, expandTime);
				}
			}
		});
	};
	
	var menuBaseSelector = '.app-sidebar .menu > .menu-item.has-sub';
	var submenuBaseSelector = ' > .menu-submenu > .menu-item.has-sub';

	// menu
	var menuLinkSelector =  menuBaseSelector + ' > .menu-link';
	var menus = [].slice.call(document.querySelectorAll(menuLinkSelector));
	handleSidebarMenuToggle(menus);

	// submenu lvl 1
	var submenuLvl1Selector = menuBaseSelector + submenuBaseSelector;
	var submenusLvl1 = [].slice.call(document.querySelectorAll(submenuLvl1Selector + ' > .menu-link'));
	handleSidebarMenuToggle(submenusLvl1);

	// submenu lvl 2
	var submenuLvl2Selector = menuBaseSelector + submenuBaseSelector + submenuBaseSelector;
	var submenusLvl2 = [].slice.call(document.querySelectorAll(submenuLvl2Selector + ' > .menu-link'));
	handleSidebarMenuToggle(submenusLvl2);
	
	
	handleSidebarMinifyFloatMenu();
});
</script>
<template>
	<div id="sidebar" class="app-sidebar">
		<perfect-scrollbar class="app-sidebar-content">
			<div class="menu">
				<template v-for="menu in appSidebarMenu">
					<div class="menu-header" v-if="menu.is_header">{{ menu.text }}</div>
					<div class="menu-divider" v-else-if="menu.is_divider"></div>
					<template v-else>
						<sidebar-nav v-if="menu.text" v-bind:menu="menu"></sidebar-nav>
					</template>
				</template>
				<div class="p-3 px-4 mt-auto hide-on-minified">
					<a href="https://seantheme.com/studio-vue/documentation/index.html" target="_blank" class="btn d-block btn-secondary fw-600 rounded-pill">
						<i class="fa fa-code-branch me-1 ms-n1 opacity-5"></i> Documentation
					</a>
				</div>
			</div>
		</perfect-scrollbar>
		<button class="app-sidebar-mobile-backdrop" v-on:click="appSidebarMobileToggled"></button>
	</div>
</template>