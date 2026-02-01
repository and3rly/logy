<script>
import { useAppOptionStore } from '@/stores/app-option';
import { RouterLink } from 'vue-router';

const appOption = useAppOptionStore();

export default {
	mounted() {
		appOption.appSidebarHide = true;
		appOption.appHeaderHide = true;
		appOption.appContentClass = 'p-0';
	},
	beforeUnmount() {
		appOption.appSidebarHide = false;
		appOption.appHeaderHide = false;
		appOption.appContentClass = '';
	}
}
</script>
<template>
	<!-- BEGIN error -->
		<div class="error-page">
			<!-- BEGIN error-page-content -->
			<div class="error-page-content">
				<div class="error-img">
					<div class="error-img-code">404</div>
					<img src="/assets/img/page/404.svg" alt="" />
				</div>
				
				<h1>Oops!</h1> 
				<h3>We can't seem to find the page you're looking for</h3>
				<p class="text-muted mb-2">
					Here are some helpful links instead:
				</p>
				<p class="mb-4">
					<RouterLink to="/" class="text-decoration-none">Home</RouterLink>
					<span class="link-divider"></span>
					<RouterLink to="/page/search-results" class="text-decoration-none">Search</RouterLink>
					<span class="link-divider"></span>
					<RouterLink to="/email/inbox" class="text-decoration-none">Email</RouterLink>
					<span class="link-divider"></span>
					<RouterLink to="/calendar" class="text-decoration-none">Calendar</RouterLink>
					<span class="link-divider"></span>
					<RouterLink to="/settings" class="text-decoration-none">Settings</RouterLink>
					<span class="link-divider"></span>
					<RouterLink to="/helper" class="text-decoration-none">Helper</RouterLink>
				</p>
				<RouterLink to="/" class="btn btn-theme">Go to Homepage</RouterLink>
			</div>
			<!-- END error-page-content -->
		</div>
		<!-- END error -->
</template>