<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl mx-auto">
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-900">Student Registration</h1>
            <p class="mt-3 text-lg text-gray-600">Complete your profile to activate your account</p>
        </div>

        @if($isCompleted)
            <div class="bg-white shadow-xl rounded-2xl p-10">
                <div class="text-center">
                    <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-green-100">
                        <svg class="h-12 w-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h2 class="mt-6 text-3xl font-bold text-gray-900">Registration Completed!</h2>
                    <p class="mt-3 text-lg text-gray-600">Your account has been activated successfully.</p>
                    <p class="mt-6 text-sm text-gray-500">You can now close this window.</p>
                </div>
            </div>
        @elseif($isExpired)
            <div class="bg-white shadow-xl rounded-2xl p-10">
                <div class="text-center">
                    <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-red-100">
                        <svg class="h-12 w-12 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h2 class="mt-6 text-3xl font-bold text-gray-900">Registration Link Expired</h2>
                    <p class="mt-3 text-lg text-gray-600">This registration link has expired.</p>
                    <p class="mt-6 text-sm text-gray-500">Please contact the school administrator for a new registration link.</p>
                </div>
            </div>
        @elseif($error)
            <div class="bg-white shadow-xl rounded-2xl p-10">
                <div class="text-center">
                    <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-red-100">
                        <svg class="h-12 w-12 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                    <h2 class="mt-6 text-3xl font-bold text-gray-900">Invalid Link</h2>
                    <p class="mt-3 text-lg text-gray-600">{{ $error }}</p>
                </div>
            </div>
        @elseif($isValid)
            <div class="bg-white shadow-xl rounded-2xl p-8 md:p-10">
                <form wire:submit.prevent="submit">
                    <div class="mb-8 pb-6 border-b border-gray-200">
                        <h3 class="text-2xl font-bold text-gray-900">Student: {{ $student->full_name }}</h3>
                        <p class="mt-2 text-sm text-gray-500">Please fill in all required fields marked with *</p>
                    </div>

                    <div class="space-y-6">
                        <!-- Personal Information -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="date_of_birth" class="block text-sm font-semibold text-gray-700 mb-2">Date of Birth *</label>
                                <input type="date" id="date_of_birth" wire:model="date_of_birth" required
                                       class="block w-full px-4 py-3 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 transition">
                                @error('date_of_birth') <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label for="gender" class="block text-sm font-semibold text-gray-700 mb-2">Gender *</label>
                                <select id="gender" wire:model="gender" required
                                        class="block w-full px-4 py-3 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 transition">
                                    <option value="">Select Gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                                @error('gender') <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div>
                            <label for="address" class="block text-sm font-semibold text-gray-700 mb-2">Address *</label>
                            <textarea id="address" wire:model="address" required rows="3"
                                      class="block w-full px-4 py-3 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 transition"></textarea>
                            @error('address') <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="previous_school" class="block text-sm font-semibold text-gray-700 mb-2">Previous School</label>
                            <input type="text" id="previous_school" wire:model="previous_school"
                                   class="block w-full px-4 py-3 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 transition">
                            @error('previous_school') <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Parent/Guardian Information -->
                        <div class="pt-6 mt-6 border-t border-gray-200">
                            <h4 class="text-xl font-bold text-gray-900 mb-6">Parent/Guardian Information</h4>
                            
                            <div class="space-y-6">
                                <div>
                                    <label for="parent_name" class="block text-sm font-semibold text-gray-700 mb-2">Parent/Guardian Name *</label>
                                    <input type="text" id="parent_name" wire:model="parent_name" required
                                           class="block w-full px-4 py-3 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 transition">
                                    @error('parent_name') <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="parent_phone" class="block text-sm font-semibold text-gray-700 mb-2">Parent/Guardian Phone *</label>
                                        <input type="tel" id="parent_phone" wire:model="parent_phone" required
                                               class="block w-full px-4 py-3 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 transition">
                                        @error('parent_phone') <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label for="parent_email" class="block text-sm font-semibold text-gray-700 mb-2">Parent/Guardian Email</label>
                                        <input type="email" id="parent_email" wire:model="parent_email"
                                               class="block w-full px-4 py-3 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 transition">
                                        @error('parent_email') <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button - More prominent -->
                    <div class="mt-10 pt-6 border-t border-gray-200">
                        <button type="submit"
                                class="w-full flex justify-center items-center py-4 px-6 border border-transparent rounded-xl shadow-lg text-base font-bold text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-4 focus:ring-blue-500 focus:ring-offset-2 transform transition hover:scale-[1.02] active:scale-[0.98]">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Complete Registration
                        </button>
                    </div>
                </form>
            </div>
        @endif
    </div>
</div>
