<template>
	<div v-if="inicio" class="text-center py-5">
		<div class="spinner-border" role="status">
			<span class="visually-hidden">Cargando...</span>
		</div>
		<div class="text-muted mt-2">
			<small>Cargando configuración...</small>
		</div>
	</div>

	<div class="container px-5" v-else>
		<div class="row justify-content-center">
			<div class="col-sm-12">
				<h4>
					<i class="fas fa-building me-2"></i> Parámetros por Empresa
				</h4>
				<p class="text-muted">Configura los parámetros generales de tu empresa</p>

				<div class="card shadow-sm mt-4">
					<div class="card-header">
						<h6 class="mb-0">
							<i class="fas fa-edit me-1"></i> Documentos
						</h6>
						<small class="text-muted">Configura los prefijos para tus documentos</small>
					</div>
					<div class="list-group list-group-flush">
						<div class="list-group-item d-flex align-items-center py-3 gap-3">
							<span class="badge bg-primary bg-opacity-10 text-primary rounded-3 p-2 me-2">
								<i class="fas fa-shopping-cart"></i>
							</span>
							<div class="flex-fill">
								<div class="fw-semibold">Orden de Compra</div>
								<small class="text-muted font-monospace" v-if="form.abr_compra">{{ form.abr_compra }}-00001</small>
							</div>
							<div>
								<input
									type="text"
									class="form-control text-center fw-bold text-uppercase"
									v-model="form.abr_compra"
									maxlength="5"
								/>
							</div>
						</div>

						<div class="list-group-item d-flex align-items-center py-3 gap-3">
							<span class="badge bg-success bg-opacity-10 text-success rounded-3 p-2 me-2">
								<i class="fas fa-dollar-sign"></i>
							</span>
							<div class="flex-fill">
								<div class="fw-semibold">Venta</div>
								<small class="text-muted font-monospace" v-if="form.abr_venta">{{ form.abr_venta }}-00001</small>
							</div>
							<div>
								<input
									type="text"
									class="form-control text-center fw-bold text-uppercase"
									v-model="form.abr_venta"
									maxlength="5"
								/>
							</div>
						</div>

						<div class="list-group-item d-flex align-items-center py-3 gap-3">
							<span class="badge bg-warning bg-opacity-10 text-warning rounded-3 p-2 me-2">
								<i class="fas fa-file-alt"></i>
							</span>
							<div class="flex-fill">
								<div class="fw-semibold">Cotización</div>
								<small class="text-muted font-monospace" v-if="form.abr_cotizacion">{{ form.abr_cotizacion }}-00001</small>
							</div>
							<div>
								<input
									type="text"
									class="form-control text-center fw-bold text-uppercase"
									v-model="form.abr_cotizacion"
									maxlength="5"
								/>
							</div>
						</div>
					</div>
				</div>

				<div class="d-flex justify-content-end gap-2 mt-3">
					<button class="btn btn-primary px-4" @click="guardar" :disabled="btnGuardar">
						<span v-if="btnGuardar">
							<i class="fas fa-spinner fa-spin me-1"></i> Guardando...
						</span>
						<span v-else>
							<i class="fas fa-save me-1"></i> Guardar
						</span>
					</button>
				</div>

			</div>
		</div>
	</div>
</template>

<script>
	import { useSesionStore } from "@/stores/sesion";

	export default {
		name: "Parametros",
		data: () => ({
			bform: {},
			form: {},
			btnGuardar: false,
			btnBuscar: false,
			inicio: false,
			url: "",
			reg: "",
			_key: "id"
		}),
		created() {
			this.url = "mnt/empresa_parametro"
			this.buscar()
		},
		methods: {
			buscar () {
				this.inicio    = true

				this.$http
				.get(`${this.$baseUrl}/${this.url}/buscar`, {params: this.bform})
				.then(result => {
					let res = result.data

					if (res.lista) {
						this.form  = res.lista
						this.reg = res.lista[this._key]
					} else {
						this.form  = {}
						this.tiempo = 0

						if (res.mensaje) {
							console.log(res.mensaje)
						}
					}

					this.inicio = false;

				})
				.catch(e => {
					this.inicio    = false
					console.log(e)
				});
			},
			guardar () {
				this.btnGuardar = true

				this.$http
				.post(`${this.$baseUrl}/${this.url}/guardar/${this.reg}`, this.form)
				.then(result => {
					let res = result.data

					if (res.exito) {
						this.$toast.success(res.mensaje)
						this.form = res.linea
						this.reg = res.linea[this._key]
					} else {
						this.$toast.error(res.mensaje)
					}
					this.btnGuardar = false
				})
				.catch(e => {
					console.log(e)
					this.btnGuardar = false
				});
			},
		}
	}
</script>