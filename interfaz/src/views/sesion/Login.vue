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
						<h4 class="fw-bold mb-4 text-center">Iniciar Sesión</h4>

						<div 
							v-if="mensaje != null" 
							class="alert alert-danger d-flex align-items-center fw-bold" 
							role="alert"
						>
							<i class="fas fa-triangle-exclamation me-2"></i> {{ mensaje }}
						</div>

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
									:disabled="btnIniciar"
								>
									<span 
										v-if="btnIniciar"
										class="spinner-border spinner-border-sm" 
										aria-hidden="true"
									></span>
									
									{{ !btnIniciar ? 'Iniciar' : 'Iniciando...'}}
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
			form: {},
			btnIniciar: false,
			mensaje: null
		}),
		methods: {
			async login() {
				this.btnIniciar = true
				this.mensaje =  null;

				let sesion = useSesionStore()
				let res = await sesion.iniciar_sesion(this.form)

				if (sesion.isLoggedIn) {
					this.$router.push({name: "home"})
				} else {
					this.mensaje = res.mensaje;
				}

				this.btnIniciar = false
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