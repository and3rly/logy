<template>
	<div class="login-container">
		<div class="container-fluid h-100">
			<div class="row h-100">
				<div class="col-md-7 d-none d-md-flex login-bg align-items-center justify-content-center">
					<img 
						src="/assets/img/login/logy.png" 
						class="login_logo"
					/>
				</div>

				<div class="col-md-5 d-flex align-items-center justify-content-center">
					<div class="login-card w-100">
						<h4 class="fw-bold mb-5 text-center">Iniciar Sesión</h4>

						<form @submit.prevent="login">
							<div class="mb-3">
								<label for="inputAlias" class="form-label">
									Usuario
								</label>
								<input 
									type="text"
									class="form-control"
									placeholder="Ingrese su usuario" 
									v-model="form.alias"
									style="height: 45px;" 
								/>
							</div>
							<div class="mb-4">
								<label for="inputAlias" class="form-label">
									Clave
								</label>
								<input 
									type="password"
									class="form-control"
									placeholder="Ingrese su contraseña"
									v-model="form.clave"
									style="height: 45px;"  
								/>
							</div>

							<div class="d-grid mb-4">
								<button
									type="submit"
									class="btn btn-primary btn-lg" 
									style="height: 45px;"
								>
									Iniciar
								</button>
							</div>

							<div class="text-center">
								<a href="javascript:;" class="text-decoration-none">
									¿Olvidaste tu contraseña?
								</a>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
	import { useSesionStore } from "@/stores/sesion";

	export default {
		name: "Login",
		data: () => ({
			form: {}
		}),
		methods: {
			async login() {
				let sesionStore = useSesionStore();
				let exito = await sesionStore.iniciar_sesion(this.form)

				console.log("HOLA MUNDO")
				if (exito) {
					this.$router.push({name: "/home"})
				}
			}
		}
	}
</script>

<style>
	.login-container {
		height: 100vh;
		background: #fff;
	}


	.login-bg {
		background-color: #0B1F2A;
		background-size: cover;
		background-position: center;
		position: relative;
	}

	.login-card {
		max-width: 420px;
		padding: 1rem;
	}

	.form-label {
		font-size: 0.9rem;
		font-weight: 500;

	}

	.login_logo {
		width: 700px;
		max-width: 70%;
		height: auto;
	}
</style>