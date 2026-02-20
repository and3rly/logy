<template>
	<form @submit.prevent="guardar">
		<div class="row g-2">
			<div class="col-sm-6">
				<label 
					for="inputNombre" 
					class="form-label fw-bold"
				>
					Nombre: <span class="fw-bold text-danger">*</span>
				</label>
				<input 
					id="inputNombre" 
					type="text" 
					class="form-control"
					v-model="form.nombre"
					required
				/>
			</div>

			<div class="col-sm-6">
				<label 
					for="selectDepartamento" 
					class="form-label fw-bold"
				>
					Departamento: <span class="text-danger">*</span>
				</label>
				<select 
					name="selectDepartamento" 
					id="selectDepartamento" 
					class="form-select"
					v-model="form.departamento_id"
				>
					<option :value="null">Seleccione un departamento...</option>
					<option v-for="i in cat.departamentos" :value="i.id">{{ i.nombre }}</option>
				</select>
			</div>

			<div class="col-sm-6">
				<label 
					for="selectMunicipio" 
					class="form-label fw-bold"
				>
					Municipio: <span class="text-danger">*</span>
				</label>
				<select 
					name="selectMunicipio" 
					id="selectMunicipio" 
					class="form-select"
					v-model="form.municipio_id"
				>
					<option :value="null">Seleccione un municipio...</option>
					<option v-for="i in municipios" :value="i.id">{{ i.nombre }}</option>
				</select>
			</div>

			<div class="col-sm-6">
				<label 
					for="inputCorreo" 
					class="form-label fw-bold"
				>
					Dirección:
				</label>
				<input 
					id="inputCorreo" 
					type="text" 
					class="form-control"
					v-model="form.direccion"
				/>
			</div>

			<div class="col-sm-6">
				<label 
					for="inputCorreo" 
					class="form-label fw-bold"
				>
					Correo:
				</label>
				<input 
					id="inputCorreo" 
					type="text" 
					class="form-control"
					v-model="form.correo"
				/>
			</div>

			<div class="col-sm-6">
				<label 
					for="inputTelefono" 
					class="form-label fw-bold"
				>
					Teléfono:
				</label>
				<input 
					id="inputTelefono" 
					type="text" 
					class="form-control"
					v-model="form.telefono"
				/>
			</div>

			<div class="col-sm-6" v-if="reg != ''">
				<div class="form-check form-switch">
					<input 
						class="form-check-input" 
						type="checkbox" 
						role="switch" 
						id="chkActivo" 
						:true-value="1"
						:false-value="0"
						v-model="form.activo"
					>
					<label class="form-check-label" for="chkActivo">Activo</label>
				</div>
			</div>	

			<div class="col-sm-12 text-end mt-4">
				<button
					type="button" 
					class="btn btn-secondary me-2" 
					@click="$emit('cerrar')"
					:disabled="btnGuardar"
				>
					<i class="fas fa-times me-1"></i>Cancelar
				</button>
				<button 
					type="submit" 
					class="btn btn-theme"
					:disabled="btnGuardar"
				>
					<span 
						v-if="btnGuardar"
						class="spinner-border spinner-border-sm" 
						aria-hidden="true"
					></span>
					<i v-else class="fas fa-save me-1"></i>
					
					{{ !btnGuardar ? 'Guardar' : 'Guardando...'}}
				</button>
			</div>
		</div>
	</form>
</template>

<script>
	import Logy from "@/mixins/Logy.js"

	export default {
		name: "FormSucursal",
		mixins: [Logy],
		props: {
			sucursal: {
				type: Object,
				default: null
			}
		},
		data: () => ({
			form: {},
			cat: {}
		}),
		created() {
			this._emit = true
			this.autoBuscar = false
			this.url = "mnt/sucursal"

			this.getDatos()

			if (this.sucursal != null) {
				this.setDataForm(this.sucursal)
			} else {
				this.fbase = {
					departamento_id: null,
					municipio_id: null,
					activo: 1
				}
			}
		},
		methods: {
			getDatos() {
				this.inicio = true

				this.$http
				.get(`${this.$baseUrl}/mnt/sucursal/get_datos`)
				.then(res => {
					this.inicio = false
					this.cat = res.data.cat
				}).catch(e => {
					this.inicio    = false
					console.log(e)
				});
			}
		},
		computed: {
			municipios() {
				if (this.form.departamento_id && this.cat.departamentos) {
					return this.cat.municipios.filter(e => {
						return e.departamento_id == this.form.departamento_id
					})
				}

				return []
			}
		}
	}
</script>