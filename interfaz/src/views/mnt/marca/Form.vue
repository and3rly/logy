<template>
	<form @submit.prevent="guardar">
		<div class="alert alert-light fw-bold">
			Todos los campos marcados con <span class="text-danger">*</span> son obligatorios.
		</div>

		<div class="row g-2">
			<div class="col-sm-12">
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
					v-if="reg != ''"
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
		name: "FormMarca",
		mixins: [Logy],
		props: {
			marca: {
				type: Object,
				default: null
			}
		},
		data: () => ({
			form: {},
			cat: {}
		}),
		created() {
			this.autoBuscar = false
			this._emit = true
			this.url = "mnt/marca"

			if (this.marca != null) {
				this.setDataForm(this.marca)
			} else {
				this.fbase = {
					activo: 1
				}
			}
		},
		watch: {
			marca(val) {
				if (val) {
					this.setDataForm(val)
				}
			}
		}
	}
</script>