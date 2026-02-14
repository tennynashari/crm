<template>
  <div v-if="loading" class="text-center py-12">
    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
    <p class="mt-2 text-gray-600">Loading customer...</p>
  </div>

  <div v-else-if="customer" class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <router-link to="/customers" class="text-gray-600 hover:text-gray-900">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
      </router-link>
      <h1 class="text-2xl lg:text-3xl font-bold text-gray-800">
        {{ customer.company }}
        <span v-if="customer.is_individual" class="text-sm text-gray-500 font-normal ml-2">(Individual)</span>
      </h1>
      <div class="flex space-x-2">
        <button
          @click="exportToExcel"
          :disabled="exportLoading"
          class="btn bg-green-600 hover:bg-green-700 text-white"
          title="Export customer detail to Excel"
        >
          <svg v-if="!exportLoading" class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <span v-if="exportLoading" class="inline-block animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-1"></span>
          {{ exportLoading ? 'Exporting...' : 'Export' }}
        </button>
        <router-link
          :to="`/customers/${customer.id}/edit`"
          class="btn btn-secondary"
        >
          <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
          </svg>
          Edit
        </router-link>
        <button @click="handleDelete" class="btn bg-red-600 hover:bg-red-700 text-white">
          <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
          </svg>
          Delete
        </button>
      </div>
    </div>

    <!-- Customer Info Card -->
    <div class="card">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Email</label>
            <p class="text-gray-900">{{ customer.email || '-' }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Phone</label>
            <p class="text-gray-900">{{ customer.phone || '-' }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Address</label>
            <p class="text-gray-900">{{ customer.address || '-' }}</p>
          </div>
        </div>

        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Area</label>
            <select v-model="customer.area_id" @change="updateCustomer" class="input">
              <option :value="null">No Area</option>
              <option v-for="area in areas" :key="area.id" :value="area.id">
                {{ area.name }}
              </option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Assigned Sales</label>
            <p class="text-gray-900">{{ customer.assigned_sales?.name || '-' }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Lead Status</label>
            <select v-model="customer.lead_status_id" @change="updateCustomer" class="input">
              <option :value="null">No Status</option>
              <option v-for="status in statuses" :key="status.id" :value="status.id">
                {{ status.name }}
              </option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Source</label>
            <p class="text-gray-900 capitalize">{{ customer.source }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Contacts (PICs) Card -->
    <div class="card">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-800">Contacts (PIC)</h3>
        <button
          @click="showContactModal = true"
          class="btn btn-primary btn-sm"
        >
          <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Add Contact
        </button>
      </div>

      <div v-if="customer.contacts && customer.contacts.length > 0" class="space-y-3">
        <div
          v-for="contact in customer.contacts"
          :key="contact.id"
          class="border border-gray-200 rounded-lg p-4"
        >
          <div class="flex justify-between items-start">
            <div class="flex-1">
              <div class="flex items-center space-x-2 mb-2">
                <h4 class="font-semibold text-gray-900">{{ contact.name }}</h4>
                <span v-if="contact.is_primary" class="badge bg-primary-100 text-primary-800 text-xs">
                  Primary
                </span>
              </div>
              <div class="space-y-1 text-sm text-gray-600">
                <p v-if="contact.position">
                  <span class="font-medium">Position:</span> {{ contact.position }}
                </p>
                <p v-if="contact.email">
                  <span class="font-medium">Email:</span> {{ contact.email }}
                </p>
                <p v-if="contact.whatsapp">
                  <span class="font-medium">WhatsApp:</span> {{ contact.whatsapp }}
                  <button
                    type="button"
                    @click="openWhatsApp(contact)"
                    class="ml-2 text-green-600 hover:text-green-800"
                    title="Chat on WhatsApp"
                  >
                    <svg class="w-4 h-4 inline" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                  </button>
                </p>
                <p v-if="contact.notes" class="text-gray-500 italic">
                  {{ contact.notes }}
                </p>
              </div>
            </div>
            <div class="flex space-x-1">
              <button
                type="button"
                @click="editContact(contact)"
                class="text-blue-600 hover:text-blue-800 p-1"
                title="Edit contact"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
              </button>
              <button
                type="button"
                @click="deleteContact(contact.id)"
                class="text-red-600 hover:text-red-800 p-1"
                title="Delete contact"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="text-center text-gray-500 py-4">
        No contacts added yet
      </div>
    </div>

    <!-- Next Action Card -->
    <div class="card">
      <h3 class="text-lg font-semibold text-gray-800 mb-4">Next Action</h3>
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-600 mb-1">Date</label>
          <input
            v-model="nextAction.next_action_date"
            type="date"
            class="input"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-600 mb-1">Priority</label>
          <select v-model="nextAction.next_action_priority" class="input">
            <option :value="null">No Priority</option>
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high">High</option>
          </select>
        </div>
        <div class="lg:col-span-2">
          <label class="block text-sm font-medium text-gray-600 mb-1">Action Plan</label>
          <textarea
            v-model="nextAction.next_action_plan"
            rows="3"
            class="input"
            placeholder="What needs to be done?"
          ></textarea>
        </div>
        <div class="lg:col-span-2">
          <button @click="updateNextAction" class="btn btn-primary">
            Update Next Action
          </button>
        </div>
      </div>
    </div>

    <!-- Action Buttons (Mobile Sticky) -->
    <div class="card lg:hidden fixed bottom-4 left-4 right-4 shadow-lg z-10">
      <div class="grid grid-cols-2 gap-2">
        <button @click="openEmailModal" class="btn btn-primary text-sm">
          ✉️ Email
        </button>
        <button @click="showInteractionModal = true" class="btn btn-secondary text-sm">
          📝 History
        </button>
      </div>
    </div>

    <!-- Action Buttons (Desktop) -->
    <div class="hidden lg:flex card space-x-4">
      <button @click="openEmailModal" class="btn btn-primary">
        <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
        </svg>
        Send Email
      </button>
      <button @click="showInteractionModal = true" class="btn btn-secondary">
        📝 Add History
      </button>
    </div>

    <!-- Interactions Timeline -->
    <div class="card">
      <h3 class="text-lg font-semibold text-gray-800 mb-4">Communication History</h3>
      
      <div v-if="interactions && interactions.length > 0" class="space-y-4">
        <div
          v-for="interaction in interactions"
          :key="interaction.id"
          class="border-l-4 pl-4 py-2"
          :class="{
            'border-blue-500': interaction.interaction_type.includes('email'),
            'border-green-500': interaction.channel === 'whatsapp',
            'border-gray-500': interaction.interaction_type === 'note',
          }"
        >
          <div class="flex items-start justify-between mb-1">
            <div class="flex items-center space-x-2">
              <span class="text-xs font-medium text-gray-600 uppercase">
                {{ interaction.channel || interaction.interaction_type }}
              </span>
              <span
                v-if="interaction.interaction_type.includes('inbound')"
                class="badge bg-green-100 text-green-800 text-xs"
              >
                Inbound
              </span>
              <span
                v-else-if="interaction.interaction_type.includes('outbound')"
                class="badge bg-blue-100 text-blue-800 text-xs"
              >
                Outbound
              </span>
            </div>
            <div class="flex items-center space-x-2">
              <span class="text-xs text-gray-500">
                {{ formatDateTime(interaction.interaction_at) }}
              </span>
              <button
                type="button"
                @click.stop="editInteraction(interaction)"
                class="text-blue-600 hover:text-blue-800 p-1"
                title="Edit history"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
              </button>
              <button
                type="button"
                @click.stop="deleteInteraction(interaction.id)"
                class="text-red-600 hover:text-red-800 p-1"
                title="Delete history"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
          </div>
          
          <div v-if="interaction.subject" class="font-medium text-gray-900 mb-1">
            {{ interaction.subject }}
          </div>
          
          <div class="text-sm text-gray-700">
            {{ interaction.summary || interaction.content }}
          </div>
          
          <div v-if="interaction.created_by_user" class="text-xs text-gray-500 mt-1">
            by {{ interaction.created_by_user.name }}
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="interactionPagination.last_page > 1" class="flex justify-center items-center space-x-2 mt-6 pt-4 border-t">
          <button
            @click="changeInteractionPage(interactionPagination.current_page - 1)"
            :disabled="interactionPagination.current_page === 1"
            class="px-3 py-1 rounded border text-sm"
            :class="interactionPagination.current_page === 1 ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white text-gray-700 hover:bg-gray-50'"
          >
            Previous
          </button>
          <span class="text-sm text-gray-600">
            Page {{ interactionPagination.current_page }} of {{ interactionPagination.last_page }}
          </span>
          <button
            @click="changeInteractionPage(interactionPagination.current_page + 1)"
            :disabled="interactionPagination.current_page === interactionPagination.last_page"
            class="px-3 py-1 rounded border text-sm"
            :class="interactionPagination.current_page === interactionPagination.last_page ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white text-gray-700 hover:bg-gray-50'"
          >
            Next
          </button>
        </div>
      </div>

      <div v-else class="text-center text-gray-500 py-8">
        No communication history yet
      </div>
    </div>

    <!-- Sales / Invoices Section -->
    <div class="card">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-800">Sales History</h3>
        <button
          @click="showInvoiceModal = true"
          class="btn btn-primary btn-sm"
        >
          <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Add Invoice
        </button>
      </div>

      <div v-if="invoices && invoices.length > 0" class="space-y-3">
        <div
          v-for="invoice in invoices"
          :key="invoice.id"
          class="border rounded-lg p-4 hover:border-primary-300 transition-colors"
        >
          <div class="flex items-start justify-between mb-2">
            <div>
              <div class="flex items-center space-x-2">
                <span class="font-semibold text-gray-900">{{ invoice.invoice_number }}</span>
                <span
                  class="badge text-xs"
                  :class="{
                    'bg-gray-100 text-gray-800': invoice.status === 'draft',
                    'bg-blue-100 text-blue-800': invoice.status === 'sent',
                    'bg-green-100 text-green-800': invoice.status === 'paid',
                    'bg-red-100 text-red-800': invoice.status === 'cancelled',
                  }"
                >
                  {{ invoice.status.toUpperCase() }}
                </span>
              </div>
              <div class="text-sm text-gray-600 mt-1">
                {{ formatDate(invoice.invoice_date) }}
                <span v-if="invoice.due_date"> - Due: {{ formatDate(invoice.due_date) }}</span>
              </div>
            </div>
            <div class="text-right">
              <div class="text-lg font-bold text-gray-900">
                Rp {{ formatNumber(invoice.total) }}
              </div>
              <div class="flex items-center space-x-1 mt-1">
                <button
                  type="button"
                  @click="editInvoice(invoice)"
                  class="text-blue-600 hover:text-blue-800 p-1"
                  title="Edit invoice"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>
                <button
                  type="button"
                  @click="deleteInvoice(invoice.id)"
                  class="text-red-600 hover:text-red-800 p-1"
                  title="Delete invoice"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </div>
          </div>

          <!-- Invoice Items -->
          <div class="mt-3 pt-3 border-t">
            <div class="text-xs font-medium text-gray-600 mb-2">Items:</div>
            <div class="space-y-1">
              <div
                v-for="item in invoice.items"
                :key="item.id"
                class="flex justify-between text-sm"
              >
                <div>
                  <span class="text-gray-900">{{ item.item_name }}</span>
                  <span v-if="item.description" class="text-gray-500 text-xs ml-1">({{ item.description }})</span>
                  <span class="text-gray-600 ml-2">x{{ item.quantity }}</span>
                </div>
                <span class="text-gray-900">Rp {{ formatNumber(item.total_price) }}</span>
              </div>
            </div>
          </div>

          <div v-if="invoice.notes" class="mt-2 text-sm text-gray-600 italic">
            Note: {{ invoice.notes }}
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="invoicePagination.last_page > 1" class="flex justify-center items-center space-x-2 mt-6 pt-4 border-t">
          <button
            @click="changeInvoicePage(invoicePagination.current_page - 1)"
            :disabled="invoicePagination.current_page === 1"
            class="px-3 py-1 rounded border text-sm"
            :class="invoicePagination.current_page === 1 ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white text-gray-700 hover:bg-gray-50'"
          >
            Previous
          </button>
          <span class="text-sm text-gray-600">
            Page {{ invoicePagination.current_page }} of {{ invoicePagination.last_page }}
          </span>
          <button
            @click="changeInvoicePage(invoicePagination.current_page + 1)"
            :disabled="invoicePagination.current_page === invoicePagination.last_page"
            class="px-3 py-1 rounded border text-sm"
            :class="invoicePagination.current_page === invoicePagination.last_page ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white text-gray-700 hover:bg-gray-50'"
          >
            Next
          </button>
        </div>
      </div>

      <div v-else class="text-center text-gray-500 py-8">
        No sales history yet
      </div>
    </div>

    <!-- Add/Edit Interaction Modal -->
    <div
      v-if="showInteractionModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
      @click.self="showInteractionModal = false"
    >
      <div class="bg-white rounded-lg max-w-lg w-full p-6">
        <h3 class="text-lg font-semibold mb-4">
          {{ editingInteraction ? 'Edit' : 'Add' }} Communication History
        </h3>
        
        <form @submit.prevent="editingInteraction ? updateInteraction() : addInteraction()" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Channel</label>
            <select v-model="interactionForm.channel" required class="input">
              <option value="">Select channel</option>
              <option value="whatsapp">WhatsApp</option>
              <option value="telephone">Telephone</option>
              <option value="instagram">Instagram</option>
              <option value="tiktok">TikTok</option>
              <option value="tokopedia">Tokopedia</option>
              <option value="shopee">Shopee</option>
              <option value="lazada">Lazada</option>
              <option value="website_chat">Website Chat</option>
              <option value="other">Other</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Date & Time</label>
            <input
              v-model="interactionForm.interaction_at"
              type="datetime-local"
              required
              class="input"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Summary</label>
            <textarea
              v-model="interactionForm.summary"
              rows="4"
              required
              class="input"
              placeholder="What was discussed or the outcome of the communication?"
            ></textarea>
          </div>

          <div class="flex space-x-3">
            <button type="submit" class="btn btn-primary flex-1">
              {{ editingInteraction ? 'Update' : 'Save' }} History
            </button>
            <button
              type="button"
              @click="closeInteractionModal"
              class="btn btn-secondary"
            >
              Cancel
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Send Email Modal -->
    <div
      v-if="showEmailModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
      @click.self="showEmailModal = false"
    >
      <div class="bg-white rounded-lg max-w-3xl w-full p-6 max-h-[90vh] overflow-y-auto">
        <h3 class="text-lg font-semibold mb-4">Send Email</h3>
        
        <form @submit.prevent="sendEmail" class="space-y-4">
          <EmailEditor 
            v-model="emailForm" 
            @update:files="emailFiles = $event"
          />

          <div class="flex justify-end space-x-3">
            <button
              type="button"
              @click="showEmailModal = false"
              class="btn btn-secondary"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="btn btn-primary"
              :disabled="sendingEmail"
            >
              <svg v-if="sendingEmail" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ sendingEmail ? 'Sending...' : 'Send Email' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Add/Edit Contact Modal -->
    <div
      v-if="showContactModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
      @click.self="closeContactModal"
    >
      <div class="bg-white rounded-lg max-w-lg w-full p-6">
        <h3 class="text-lg font-semibold mb-4">
          {{ editingContact ? 'Edit' : 'Add' }} Contact
        </h3>
        
        <form @submit.prevent="editingContact ? updateContact() : addContact()" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
            <input
              v-model="contactForm.name"
              type="text"
              required
              class="input"
              placeholder="Contact name"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Position</label>
            <input
              v-model="contactForm.position"
              type="text"
              class="input"
              placeholder="e.g., Director, Manager"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">WhatsApp</label>
            <input
              v-model="contactForm.whatsapp"
              type="text"
              class="input"
              placeholder="+628123456789"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input
              v-model="contactForm.email"
              type="email"
              class="input"
              placeholder="email@example.com"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
            <textarea
              v-model="contactForm.notes"
              rows="2"
              class="input"
              placeholder="Additional notes about this contact"
            ></textarea>
          </div>

          <div>
            <label class="flex items-center">
              <input
                v-model="contactForm.is_primary"
                type="checkbox"
                class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
              />
              <span class="ml-2 text-sm text-gray-700">Primary Contact</span>
            </label>
          </div>

          <div class="flex space-x-3">
            <button type="submit" class="btn btn-primary flex-1">
              {{ editingContact ? 'Update' : 'Add' }} Contact
            </button>
            <button
              type="button"
              @click="closeContactModal"
              class="btn btn-secondary"
            >
              Cancel
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Add/Edit Invoice Modal -->
    <div
      v-if="showInvoiceModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
      @click.self="closeInvoiceModal"
    >
      <div class="bg-white rounded-lg max-w-3xl w-full p-6 max-h-[90vh] overflow-y-auto">
        <h3 class="text-lg font-semibold mb-4">
          {{ editingInvoice ? 'Edit' : 'Add' }} Invoice
        </h3>
        
        <form @submit.prevent="editingInvoice ? updateInvoice() : addInvoice()" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Invoice Number
                <span v-if="!editingInvoice" class="text-xs text-gray-500">(auto-generate if empty)</span>
              </label>
              <input
                v-model="invoiceForm.invoice_number"
                type="text"
                placeholder="INV-20260208-0001"
                class="input"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Invoice Date *</label>
              <input
                v-model="invoiceForm.invoice_date"
                type="date"
                required
                class="input"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Due Date</label>
              <input
                v-model="invoiceForm.due_date"
                type="date"
                class="input"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
              <select v-model="invoiceForm.status" required class="input">
                <option value="draft">Draft</option>
                <option value="sent">Sent</option>
                <option value="paid">Paid</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>
          </div>

          <!-- Invoice Items -->
          <div class="border-t pt-4">
            <div class="flex justify-between items-center mb-3">
              <label class="block text-sm font-medium text-gray-700">Invoice Items *</label>
              <button
                type="button"
                @click="addInvoiceItem"
                class="text-sm text-primary-600 hover:text-primary-700 flex items-center"
              >
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Item
              </button>
            </div>

            <div class="space-y-3">
              <div
                v-for="(item, index) in invoiceForm.items"
                :key="index"
                class="border rounded p-3 bg-gray-50"
              >
                <div class="flex justify-between items-start mb-2">
                  <span class="text-sm font-medium text-gray-700">Item {{ index + 1 }}</span>
                  <button
                    type="button"
                    @click="removeInvoiceItem(index)"
                   class="text-red-600 hover:text-red-800"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                  <div>
                    <input
                      v-model="item.item_name"
                      type="text"
                      placeholder="Item name *"
                      required
                      class="input text-sm"
                    />
                  </div>
                  <div>
                    <input
                      v-model="item.description"
                      type="text"
                      placeholder="Description (optional)"
                      class="input text-sm"
                    />
                  </div>
                  <div>
                    <input
                      v-model.number="item.quantity"
                      type="number"
                      min="1"
                      placeholder="Quantity *"
                      required
                      class="input text-sm"
                    />
                  </div>
                  <div>
                    <input
                      v-model.number="item.unit_price"
                      type="number"
                      min="0"
                      step="0.01"
                      placeholder="Unit price *"
                      required
                      class="input text-sm"
                    />
                  </div>
                </div>

                <div class="mt-2 text-right text-sm font-medium text-gray-700">
                  Subtotal: Rp {{ formatNumber((item.quantity || 0) * (item.unit_price || 0)) }}
                </div>
              </div>
            </div>
          </div>

          <!-- Totals -->
          <div class="border-t pt-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tax</label>
                <input
                  v-model.number="invoiceForm.tax"
                  type="number"
                  min="0"
                  step="0.01"
                  class="input"
                  placeholder="0"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Discount</label>
                <input
                  v-model.number="invoiceForm.discount"
                  type="number"
                  min="0"
                  step="0.01"
                  class="input"
                  placeholder="0"
                />
              </div>
            </div>

            <div class="mt-4 p-3 bg-gray-100 rounded">
              <div class="flex justify-between text-sm mb-1">
                <span class="text-gray-600">Subtotal:</span>
                <span class="text-gray-900">Rp {{ formatNumber(calculateSubtotal()) }}</span>
              </div>
              <div class="flex justify-between text-sm mb-1">
                <span class="text-gray-600">Tax:</span>
                <span class="text-gray-900">Rp {{ formatNumber(invoiceForm.tax || 0) }}</span>
              </div>
              <div class="flex justify-between text-sm mb-2">
                <span class="text-gray-600">Discount:</span>
                <span class="text-gray-900">- Rp {{ formatNumber(invoiceForm.discount || 0) }}</span>
              </div>
              <div class="flex justify-between text-lg font-bold border-t pt-2">
                <span>Total:</span>
                <span>Rp {{ formatNumber(calculateTotal()) }}</span>
              </div>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
            <textarea
              v-model="invoiceForm.notes"
              rows="3"
              class="input"
              placeholder="Additional notes for this invoice"
            ></textarea>
          </div>

          <div class="flex space-x-3">
            <button type="submit" class="btn btn-primary flex-1">
              {{ editingInvoice ? 'Update' : 'Save' }} Invoice
            </button>
            <button
              type="button"
              @click="closeInvoiceModal"
              class="btn btn-secondary"
            >
              Cancel
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useCustomerStore } from '@/stores/customer'
import { useInteractionStore } from '@/stores/interaction'
import { useContactStore } from '@/stores/contact'
import { useAreaStore } from '@/stores/area'
import { useLeadStatusStore } from '@/stores/leadStatus'
import { useEmailSettingStore } from '@/stores/emailSetting'
import { useInvoiceStore } from '@/stores/invoice'
import EmailEditor from '@/components/EmailEditor.vue'
import api from '@/api/axios'

const route = useRoute()
const router = useRouter()
const customerStore = useCustomerStore()
const interactionStore = useInteractionStore()
const contactStore = useContactStore()
const areaStore = useAreaStore()
const leadStatusStore = useLeadStatusStore()
const emailSettingStore = useEmailSettingStore()
const invoiceStore = useInvoiceStore()

const customer = computed(() => customerStore.currentCustomer)
const loading = computed(() => customerStore.loading)
const areas = computed(() => areaStore.areas)
const statuses = computed(() => leadStatusStore.statuses)

const interactions = ref([])
const interactionPagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
})

const showInteractionModal = ref(false)
const showContactModal = ref(false)
const showEmailModal = ref(false)
const editingInteraction = ref(null)
const editingContact = ref(null)

const nextAction = ref({
  next_action_date: null,
  next_action_plan: null,
  next_action_priority: null,
})

const interactionForm = ref({
  channel: '',
  interaction_at: '',
  summary: '',
})

const contactForm = ref({
  name: '',
  position: '',
  whatsapp: '',
  email: '',
  notes: '',
  is_primary: false,
})

const emailForm = ref({
  to: '',
  subject: '',
  body: '',
})

const emailFiles = ref([])
const sendingEmail = ref(false)

// Invoice data
const invoices = ref([])
const invoicePagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
})
const showInvoiceModal = ref(false)
const editingInvoice = ref(null)
const exportLoading = ref(false)
const invoiceForm = ref({
  invoice_number: '',
  invoice_date: '',
  due_date: '',
  status: 'draft',
  tax: 0,
  discount: 0,
  notes: '',
  items: [
    { item_name: '', description: '', quantity: 1, unit_price: 0 }
  ],
})

const openEmailModal = () => {
  // Auto-fill email: company email + all PIC emails
  const emails = []
  
  // Add company email if exists
  if (customer.value.email) {
    emails.push(customer.value.email)
  }
  
  // Add all PIC emails
  customer.value.contacts?.forEach(contact => {
    if (contact.email && !emails.includes(contact.email)) {
      emails.push(contact.email)
    }
  })
  
  emailForm.value.to = emails.join(', ')
  emailForm.value.subject = `Regarding ${customer.value.company}`
  emailForm.value.body = ''
  
  showEmailModal.value = true
}

const sendEmail = async () => {
  sendingEmail.value = true
  try {
    // Create FormData for file upload support
    const formData = new FormData()
    formData.append('customer_id', route.params.id)
    formData.append('to', emailForm.value.to)
    formData.append('subject', emailForm.value.subject)
    formData.append('body', emailForm.value.body)
    
    // Add attachments
    emailFiles.value.forEach((file, index) => {
      formData.append(`attachments[${index}]`, file)
    })
    
    await emailSettingStore.sendEmailWithAttachments(formData)
    showEmailModal.value = false
    alert('Email sent successfully!')
    emailForm.value = {
      to: '',
      subject: '',
      body: '',
    }
    emailFiles.value = []
    // Refresh interactions to show the new email log
    await fetchInteractions(interactionPagination.value.current_page)
  } catch (error) {
    if (error.response?.status === 400) {
      alert('Please configure your email settings first in Settings page')
      router.push('/settings')
    } else {
      alert('Failed to send email: ' + (error.response?.data?.message || error.message))
    }
  } finally {
    sendingEmail.value = false
  }
}

const updateCustomer = async () => {
  try {
    await customerStore.updateCustomer(customer.value.id, {
      area_id: customer.value.area_id,
      lead_status_id: customer.value.lead_status_id,
    })
    alert('Customer updated successfully')
  } catch (error) {
    alert('Failed to update customer')
  }
}

const updateNextAction = async () => {
  try {
    await customerStore.updateNextAction(customer.value.id, nextAction.value)
    alert('Next action updated successfully')
    await customerStore.fetchCustomer(route.params.id)
  } catch (error) {
    alert('Failed to update next action')
  }
}

const addInteraction = async () => {
  try {
    await interactionStore.createInteraction({
      customer_id: customer.value.id,
      interaction_type: 'manual_channel',
      channel: interactionForm.value.channel,
      summary: interactionForm.value.summary,
      interaction_at: interactionForm.value.interaction_at,
    })
    closeInteractionModal()
    alert('History added successfully')
    await customerStore.fetchCustomer(route.params.id)
    await fetchInteractions(interactionPagination.value.current_page)
  } catch (error) {
    alert('Failed to add history')
  }
}

const editInteraction = (interaction) => {
  editingInteraction.value = interaction
  interactionForm.value = {
    channel: interaction.channel,
    interaction_at: interaction.interaction_at.slice(0, 16), // Format for datetime-local
    summary: interaction.summary || interaction.content,
  }
  showInteractionModal.value = true
}

const updateInteraction = async () => {
  try {
    await interactionStore.updateInteraction(editingInteraction.value.id, {
      channel: interactionForm.value.channel,
      summary: interactionForm.value.summary,
      interaction_at: interactionForm.value.interaction_at,
    })
    closeInteractionModal()
    alert('History updated successfully')
    await customerStore.fetchCustomer(route.params.id)
    await fetchInteractions(interactionPagination.value.current_page)
  } catch (error) {
    alert('Failed to update history')
  }
}

const closeInteractionModal = () => {
  showInteractionModal.value = false
  editingInteraction.value = null
  // Reset form
  interactionForm.value = {
    channel: '',
    interaction_at: '',
    summary: '',
  }
}

const openWhatsApp = (contact) => {
  if (contact && contact.whatsapp) {
    const phone = contact.whatsapp.replace(/\D/g, '')
    window.open(`https://wa.me/${phone}`, '_blank')
  } else {
    alert('No WhatsApp number available for this contact')
  }
}

const openTelephone = () => {
  if (customer.value.phone) {
    window.open(`tel:${customer.value.phone}`, '_self')
  } else {
    alert('No phone number available')
  }
}

const formatDateTime = (date) => {
  return new Date(date).toLocaleString('id-ID', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const fetchInteractions = async (page = 1) => {
  try {
    const response = await interactionStore.fetchInteractions({
      customer_id: route.params.id,
      page,
      per_page: 10,
    })
    interactions.value = response.data
    interactionPagination.value = {
      current_page: response.current_page,
      last_page: response.last_page,
      per_page: response.per_page,
      total: response.total,
    }
  } catch (error) {
    console.error('Error fetching interactions:', error)
  }
}

const changeInteractionPage = async (page) => {
  if (page < 1 || page > interactionPagination.value.last_page) return
  await fetchInteractions(page)
}

const handleDelete = async () => {
  if (!confirm(`Are you sure you want to delete ${customer.value.company}? This action cannot be undone.`)) {
    return
  }

  try {
    await customerStore.deleteCustomer(customer.value.id)
    alert('Customer deleted successfully')
    router.push('/customers')
  } catch (error) {
    alert('Failed to delete customer')
  }
}

const deleteInteraction = async (interactionId) => {
  if (!confirm('Are you sure you want to delete this history entry?')) {
    return
  }

  try {
    await interactionStore.deleteInteraction(interactionId)
    alert('History deleted successfully')
    // Reload customer data to refresh interactions
    await customerStore.fetchCustomer(route.params.id)
    await fetchInteractions(interactionPagination.value.current_page)
  } catch (error) {
    alert('Failed to delete history')
  }
}

// Contact Management
const addContact = async () => {
  try {
    await contactStore.createContact({
      customer_id: customer.value.id,
      ...contactForm.value,
    })
    closeContactModal()
    alert('Contact added successfully')
    await customerStore.fetchCustomer(route.params.id)
  } catch (error) {
    alert('Failed to add contact')
  }
}

const editContact = (contact) => {
  editingContact.value = contact
  contactForm.value = {
    name: contact.name,
    position: contact.position || '',
    whatsapp: contact.whatsapp || '',
    email: contact.email || '',
    notes: contact.notes || '',
    is_primary: contact.is_primary,
  }
  showContactModal.value = true
}

const updateContact = async () => {
  try {
    await contactStore.updateContact(editingContact.value.id, contactForm.value)
    closeContactModal()
    alert('Contact updated successfully')
    await customerStore.fetchCustomer(route.params.id)
  } catch (error) {
    alert('Failed to update contact')
  }
}

const deleteContact = async (contactId) => {
  if (!confirm('Are you sure you want to delete this contact?')) {
    return
  }

  try {
    await contactStore.deleteContact(contactId)
    alert('Contact deleted successfully')
    await customerStore.fetchCustomer(route.params.id)
  } catch (error) {
    alert('Failed to delete contact')
  }
}

const closeContactModal = () => {
  showContactModal.value = false
  editingContact.value = null
  contactForm.value = {
    name: '',
    position: '',
    whatsapp: '',
    email: '',
    notes: '',
    is_primary: false,
  }
}

// Invoice functions
const fetchInvoices = async (page = 1) => {
  try {
    const response = await invoiceStore.fetchInvoices({
      customer_id: route.params.id,
      page,
      per_page: 10,
    })
    invoices.value = response.data
    invoicePagination.value = {
      current_page: response.current_page,
      last_page: response.last_page,
      per_page: response.per_page,
      total: response.total,
    }
  } catch (error) {
    console.error('Error fetching invoices:', error)
  }
}

const changeInvoicePage = (page) => {
  if (page >= 1 && page <= invoicePagination.value.last_page) {
    fetchInvoices(page)
  }
}

const addInvoiceItem = () => {
  invoiceForm.value.items.push({
    item_name: '',
    description: '',
    quantity: 1,
    unit_price: 0,
  })
}

const removeInvoiceItem = (index) => {
  if (invoiceForm.value.items.length > 1) {
    invoiceForm.value.items.splice(index, 1)
  }
}

const calculateSubtotal = () => {
  return invoiceForm.value.items.reduce((sum, item) => {
    return sum + ((item.quantity || 0) * (item.unit_price || 0))
  }, 0)
}

const calculateTotal = () => {
  const subtotal = calculateSubtotal()
  const tax = invoiceForm.value.tax || 0
  const discount = invoiceForm.value.discount || 0
  return subtotal + tax - discount
}

const addInvoice = async () => {
  try {
    await invoiceStore.createInvoice({
      customer_id: customer.value.id,
      ...invoiceForm.value,
    })
    closeInvoiceModal()
    alert('Invoice created successfully')
    await fetchInvoices(invoicePagination.value.current_page)
  } catch (error) {
    alert('Failed to create invoice: ' + (error.response?.data?.message || error.message))
  }
}

const editInvoice = (invoice) => {
  editingInvoice.value = invoice
  invoiceForm.value = {
    invoice_number: invoice.invoice_number || '',
    invoice_date: invoice.invoice_date,
    due_date: invoice.due_date || '',
    status: invoice.status,
    tax: parseFloat(invoice.tax) || 0,
    discount: parseFloat(invoice.discount) || 0,
    notes: invoice.notes || '',
    items: invoice.items.map(item => ({
      item_name: item.item_name,
      description: item.description || '',
      quantity: item.quantity,
      unit_price: parseFloat(item.unit_price),
    })),
  }
  showInvoiceModal.value = true
}

const updateInvoice = async () => {
  try {
    await invoiceStore.updateInvoice(editingInvoice.value.id, {
      ...invoiceForm.value,
    })
    closeInvoiceModal()
    alert('Invoice updated successfully')
    await fetchInvoices(invoicePagination.value.current_page)
  } catch (error) {
    alert('Failed to update invoice: ' + (error.response?.data?.message || error.message))
  }
}

const deleteInvoice = async (invoiceId) => {
  if (!confirm('Are you sure you want to delete this invoice?')) {
    return
  }

  try {
    await invoiceStore.deleteInvoice(invoiceId)
    alert('Invoice deleted successfully')
    await fetchInvoices(invoicePagination.value.current_page)
  } catch (error) {
    alert('Failed to delete invoice')
  }
}

const closeInvoiceModal = () => {
  showInvoiceModal.value = false
  editingInvoice.value = null
  invoiceForm.value = {
    invoice_number: '',
    invoice_date: '',
    due_date: '',
    status: 'draft',
    tax: 0,
    discount: 0,
    notes: '',
    items: [
      { item_name: '', description: '', quantity: 1, unit_price: 0 }
    ],
  }
}

const formatNumber = (number) => {
  return new Intl.NumberFormat('id-ID').format(number)
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('id-ID', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

const exportToExcel = async () => {
  exportLoading.value = true
  try {
    const response = await api.get(`/customers/${route.params.id}/export`, {
      responseType: 'blob'
    })

    // Extract filename from Content-Disposition header
    const contentDisposition = response.headers['content-disposition']
    let filename = `customer_${customer.value.company}_${new Date().toISOString().split('T')[0]}.xlsx`
    
    if (contentDisposition) {
      const filenameMatch = contentDisposition.match(/filename="?(.+)"?/)
      if (filenameMatch && filenameMatch[1]) {
        filename = filenameMatch[1]
      }
    }

    // Create blob link to download
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', filename)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Error exporting customer detail:', error)
    alert('Failed to export customer detail. Please try again.')
  } finally {
    exportLoading.value = false
  }
}

onMounted(async () => {
  await Promise.all([
    customerStore.fetchCustomer(route.params.id),
    areaStore.fetchAreas(),
    leadStatusStore.fetchStatuses(),
    fetchInteractions(1),
    fetchInvoices(1),
  ])

  // Initialize next action form
  if (customer.value) {
    nextAction.value = {
      next_action_date: customer.value.next_action_date,
      next_action_plan: customer.value.next_action_plan,
      next_action_priority: customer.value.next_action_priority,
    }
  }
})
</script>
