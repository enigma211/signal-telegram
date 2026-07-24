<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            آخرین خطاهای ارسال (تلگرام + توییتر)
        </x-slot>

        @php($errors = $this->getErrors())

        @if (count($errors) === 0)
            <p class="text-sm text-gray-500 dark:text-gray-400">
                خطای ارسال ثبت‌شده‌ای در حال حاضر نیست.
            </p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-start">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700 text-gray-500">
                            <th class="py-2 pe-3 font-medium text-start">کانال</th>
                            <th class="py-2 pe-3 font-medium text-start">نوع</th>
                            <th class="py-2 pe-3 font-medium text-start">خطا</th>
                            <th class="py-2 pe-3 font-medium text-start">زمان</th>
                            <th class="py-2 font-medium text-start"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($errors as $row)
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <td class="py-2 pe-3 whitespace-nowrap">{{ $row['channel'] }}</td>
                                <td class="py-2 pe-3 whitespace-nowrap">{{ $row['context'] }}</td>
                                <td class="py-2 pe-3 max-w-md truncate" title="{{ $row['error'] }}">{{ $row['error'] }}</td>
                                <td class="py-2 pe-3 whitespace-nowrap text-gray-500">{{ $row['when'] }}</td>
                                <td class="py-2 whitespace-nowrap">
                                    <a href="{{ $row['url'] }}" class="text-primary-600 hover:underline">جزئیات</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
