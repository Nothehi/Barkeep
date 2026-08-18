<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The Persian wording of the framework's validation messages. The key names
    | mirror the framework's own file exactly, so any rule this application
    | reaches — now or later — resolves here rather than falling back to
    | English halfway through a form.
    |
    | `:attribute` is filled from the `attributes` array at the bottom where one
    | is given, and from the field name otherwise. The field names in this
    | application are English identifiers (`player_count_min`), which read badly
    | inside a Persian sentence, so the ones that appear on user-facing forms
    | are named there.
    |
    */

    'accepted' => 'فیلد :attribute باید پذیرفته شود.',
    'accepted_if' => 'فیلد :attribute وقتی :other برابر :value است باید پذیرفته شود.',
    'active_url' => 'فیلد :attribute باید نشانی معتبری باشد.',
    'after' => 'فیلد :attribute باید تاریخی پس از :date باشد.',
    'after_or_equal' => 'فیلد :attribute باید تاریخی برابر یا پس از :date باشد.',
    'alpha' => 'فیلد :attribute تنها می‌تواند شامل حروف باشد.',
    'alpha_dash' => 'فیلد :attribute تنها می‌تواند شامل حروف، رقم، خط تیره و زیرخط باشد.',
    'alpha_num' => 'فیلد :attribute تنها می‌تواند شامل حروف و رقم باشد.',
    'any_of' => 'فیلد :attribute معتبر نیست.',
    'array' => 'فیلد :attribute باید یک آرایه باشد.',
    'array_keys' => 'فیلد :attribute باید شامل کلیدهای زیر باشد: :values.',
    'ascii' => 'فیلد :attribute تنها می‌تواند شامل نویسه‌ها و نمادهای تک‌بایتی باشد.',
    'base64' => 'فیلد :attribute باید یک رشتهٔ base64 معتبر باشد.',
    'before' => 'فیلد :attribute باید تاریخی پیش از :date باشد.',
    'before_or_equal' => 'فیلد :attribute باید تاریخی برابر یا پیش از :date باشد.',
    'between' => [
        'array' => 'فیلد :attribute باید میان :min و :max مورد داشته باشد.',
        'file' => 'اندازهٔ فیلد :attribute باید میان :min و :max کیلوبایت باشد.',
        'numeric' => 'مقدار فیلد :attribute باید میان :min و :max باشد.',
        'string' => 'فیلد :attribute باید میان :min و :max نویسه باشد.',
    ],
    'boolean' => 'فیلد :attribute باید درست یا نادرست باشد.',
    'can' => 'فیلد :attribute شامل مقداری غیرمجاز است.',
    'confirmed' => 'تأییدیهٔ فیلد :attribute همخوان نیست.',
    'contains' => 'فیلد :attribute مقداری الزامی را ندارد.',
    'current_password' => 'گذرواژه نادرست است.',
    'date' => 'فیلد :attribute باید تاریخی معتبر باشد.',
    'date_equals' => 'فیلد :attribute باید تاریخی برابر با :date باشد.',
    'date_format' => 'فیلد :attribute باید با قالب :format همخوان باشد.',
    'decimal' => 'فیلد :attribute باید :decimal رقم اعشار داشته باشد.',
    'declined' => 'فیلد :attribute باید رد شود.',
    'declined_if' => 'فیلد :attribute وقتی :other برابر :value است باید رد شود.',
    'different' => 'فیلد :attribute و :other باید متفاوت باشند.',
    'digits' => 'فیلد :attribute باید :digits رقم باشد.',
    'digits_between' => 'فیلد :attribute باید میان :min و :max رقم باشد.',
    'dimensions' => 'ابعاد تصویر فیلد :attribute نامعتبر است.',
    'distinct' => 'فیلد :attribute مقداری تکراری دارد.',
    'doesnt_contain' => 'فیلد :attribute نباید شامل هیچ‌یک از این‌ها باشد: :values.',
    'doesnt_end_with' => 'فیلد :attribute نباید به هیچ‌یک از این‌ها ختم شود: :values.',
    'doesnt_start_with' => 'فیلد :attribute نباید با هیچ‌یک از این‌ها آغاز شود: :values.',
    'email' => 'فیلد :attribute باید نشانی ایمیل معتبری باشد.',
    'encoding' => 'فیلد :attribute باید با کدگذاری :encoding نوشته شده باشد.',
    'ends_with' => 'فیلد :attribute باید به یکی از این‌ها ختم شود: :values.',
    'enum' => 'مقدار برگزیدهٔ :attribute نامعتبر است.',
    'exists' => 'مقدار برگزیدهٔ :attribute نامعتبر است.',
    'extensions' => 'فیلد :attribute باید یکی از این پسوندها را داشته باشد: :values.',
    'file' => 'فیلد :attribute باید یک پرونده باشد.',
    'filled' => 'فیلد :attribute باید مقداری داشته باشد.',
    'gt' => [
        'array' => 'فیلد :attribute باید بیش از :value مورد داشته باشد.',
        'file' => 'اندازهٔ فیلد :attribute باید بیش از :value کیلوبایت باشد.',
        'numeric' => 'مقدار فیلد :attribute باید بیش از :value باشد.',
        'string' => 'فیلد :attribute باید بیش از :value نویسه باشد.',
    ],
    'gte' => [
        'array' => 'فیلد :attribute باید :value مورد یا بیشتر داشته باشد.',
        'file' => 'اندازهٔ فیلد :attribute باید :value کیلوبایت یا بیشتر باشد.',
        'numeric' => 'مقدار فیلد :attribute باید :value یا بیشتر باشد.',
        'string' => 'فیلد :attribute باید :value نویسه یا بیشتر باشد.',
    ],
    'hex_color' => 'فیلد :attribute باید رنگ شانزده‌شانزدهی معتبری باشد.',
    'image' => 'فیلد :attribute باید یک تصویر باشد.',
    'in' => 'مقدار برگزیدهٔ :attribute نامعتبر است.',
    'in_array' => 'فیلد :attribute باید در :other وجود داشته باشد.',
    'in_array_keys' => 'فیلد :attribute باید دست‌کم یکی از این کلیدها را داشته باشد: :values.',
    'integer' => 'فیلد :attribute باید یک عدد صحیح باشد.',
    'ip' => 'فیلد :attribute باید نشانی IP معتبری باشد.',
    'ipv4' => 'فیلد :attribute باید نشانی IPv4 معتبری باشد.',
    'ipv6' => 'فیلد :attribute باید نشانی IPv6 معتبری باشد.',
    'json' => 'فیلد :attribute باید یک رشتهٔ JSON معتبر باشد.',
    'list' => 'فیلد :attribute باید یک فهرست باشد.',
    'lowercase' => 'فیلد :attribute باید با حروف کوچک نوشته شود.',
    'lt' => [
        'array' => 'فیلد :attribute باید کمتر از :value مورد داشته باشد.',
        'file' => 'اندازهٔ فیلد :attribute باید کمتر از :value کیلوبایت باشد.',
        'numeric' => 'مقدار فیلد :attribute باید کمتر از :value باشد.',
        'string' => 'فیلد :attribute باید کمتر از :value نویسه باشد.',
    ],
    'lte' => [
        'array' => 'فیلد :attribute نباید بیش از :value مورد داشته باشد.',
        'file' => 'اندازهٔ فیلد :attribute باید :value کیلوبایت یا کمتر باشد.',
        'numeric' => 'مقدار فیلد :attribute باید :value یا کمتر باشد.',
        'string' => 'فیلد :attribute باید :value نویسه یا کمتر باشد.',
    ],
    'mac_address' => 'فیلد :attribute باید نشانی MAC معتبری باشد.',
    'max' => [
        'array' => 'فیلد :attribute نباید بیش از :max مورد داشته باشد.',
        'file' => 'اندازهٔ فیلد :attribute نباید بیش از :max کیلوبایت باشد.',
        'numeric' => 'مقدار فیلد :attribute نباید بیش از :max باشد.',
        'string' => 'فیلد :attribute نباید بیش از :max نویسه باشد.',
    ],
    'max_digits' => 'فیلد :attribute نباید بیش از :max رقم داشته باشد.',
    'mimes' => 'فیلد :attribute باید پرونده‌ای از نوع :values باشد.',
    'mimetypes' => 'فیلد :attribute باید پرونده‌ای از نوع :values باشد.',
    'min' => [
        'array' => 'فیلد :attribute باید دست‌کم :min مورد داشته باشد.',
        'file' => 'اندازهٔ فیلد :attribute باید دست‌کم :min کیلوبایت باشد.',
        'numeric' => 'مقدار فیلد :attribute باید دست‌کم :min باشد.',
        'string' => 'فیلد :attribute باید دست‌کم :min نویسه باشد.',
    ],
    'min_digits' => 'فیلد :attribute باید دست‌کم :min رقم داشته باشد.',
    'missing' => 'فیلد :attribute نباید وجود داشته باشد.',
    'missing_if' => 'فیلد :attribute وقتی :other برابر :value است نباید وجود داشته باشد.',
    'missing_unless' => 'فیلد :attribute جز وقتی :other برابر :value است نباید وجود داشته باشد.',
    'missing_with' => 'فیلد :attribute وقتی :values وجود دارد نباید وجود داشته باشد.',
    'missing_with_all' => 'فیلد :attribute وقتی همهٔ :values وجود دارند نباید وجود داشته باشد.',
    'multiple_of' => 'فیلد :attribute باید مضربی از :value باشد.',
    'not_in' => 'مقدار برگزیدهٔ :attribute نامعتبر است.',
    'not_regex' => 'قالب فیلد :attribute نامعتبر است.',
    'numeric' => 'فیلد :attribute باید یک عدد باشد.',
    'password' => [
        'letters' => 'فیلد :attribute باید دست‌کم یک حرف داشته باشد.',
        'mixed' => 'فیلد :attribute باید دست‌کم یک حرف بزرگ و یک حرف کوچک داشته باشد.',
        'numbers' => 'فیلد :attribute باید دست‌کم یک رقم داشته باشد.',
        'symbols' => 'فیلد :attribute باید دست‌کم یک نماد داشته باشد.',
        'uncompromised' => 'مقدار واردشده برای :attribute در نشت داده‌ها دیده شده است. لطفاً :attribute دیگری برگزینید.',
    ],
    'present' => 'فیلد :attribute باید وجود داشته باشد.',
    'present_if' => 'فیلد :attribute وقتی :other برابر :value است باید وجود داشته باشد.',
    'present_unless' => 'فیلد :attribute جز وقتی :other برابر :value است باید وجود داشته باشد.',
    'present_with' => 'فیلد :attribute وقتی :values وجود دارد باید وجود داشته باشد.',
    'present_with_all' => 'فیلد :attribute وقتی همهٔ :values وجود دارند باید وجود داشته باشد.',
    'prohibited' => 'فیلد :attribute مجاز نیست.',
    'prohibited_if' => 'فیلد :attribute وقتی :other برابر :value است مجاز نیست.',
    'prohibited_if_accepted' => 'فیلد :attribute وقتی :other پذیرفته شده است مجاز نیست.',
    'prohibited_if_declined' => 'فیلد :attribute وقتی :other رد شده است مجاز نیست.',
    'prohibited_unless' => 'فیلد :attribute جز وقتی :other در :values باشد مجاز نیست.',
    'prohibits' => 'فیلد :attribute وجود :other را ناممکن می‌کند.',
    'regex' => 'قالب فیلد :attribute نامعتبر است.',
    'required' => 'فیلد :attribute الزامی است.',
    'required_array_keys' => 'فیلد :attribute باید شامل مدخل‌هایی برای :values باشد.',
    'required_if' => 'فیلد :attribute وقتی :other برابر :value است الزامی است.',
    'required_if_accepted' => 'فیلد :attribute وقتی :other پذیرفته شده است الزامی است.',
    'required_if_declined' => 'فیلد :attribute وقتی :other رد شده است الزامی است.',
    'required_unless' => 'فیلد :attribute جز وقتی :other در :values باشد الزامی است.',
    'required_with' => 'فیلد :attribute وقتی :values وجود دارد الزامی است.',
    'required_with_all' => 'فیلد :attribute وقتی همهٔ :values وجود دارند الزامی است.',
    'required_without' => 'فیلد :attribute وقتی :values وجود ندارد الزامی است.',
    'required_without_all' => 'فیلد :attribute وقتی هیچ‌یک از :values وجود ندارند الزامی است.',
    'same' => 'فیلد :attribute باید با :other همخوان باشد.',
    'size' => [
        'array' => 'فیلد :attribute باید :size مورد داشته باشد.',
        'file' => 'اندازهٔ فیلد :attribute باید :size کیلوبایت باشد.',
        'numeric' => 'مقدار فیلد :attribute باید :size باشد.',
        'string' => 'فیلد :attribute باید :size نویسه باشد.',
    ],
    'starts_with' => 'فیلد :attribute باید با یکی از این‌ها آغاز شود: :values.',
    'string' => 'فیلد :attribute باید یک رشته باشد.',
    'timezone' => 'فیلد :attribute باید منطقهٔ زمانی معتبری باشد.',
    'unique' => 'این :attribute پیش‌تر گرفته شده است.',
    'uploaded' => 'بارگذاری :attribute ناموفق بود.',
    'uppercase' => 'فیلد :attribute باید با حروف بزرگ نوشته شود.',
    'url' => 'فیلد :attribute باید نشانی معتبری باشد.',
    'ulid' => 'فیلد :attribute باید یک ULID معتبر باشد.',
    'uuid' => 'فیلد :attribute باید یک UUID معتبر باشد.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Messages for one field and one rule, named "field.rule". Nothing needs
    | one yet: the domain's own refusals are worded by the exceptions that
    | raise them, not by validation.
    |
    */

    'custom' => [],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The field names as a reader should see them. Without these, `:attribute`
    | is filled with the request key — "player_count_min" — which is a database
    | column appearing mid-sentence in a language that does not use it.
    |
    | Only fields that appear on a form somebody fills in are listed. Keys that
    | never reach a validation message do not need a name here.
    |
    */

    'attributes' => [
        'audience' => 'مخاطب هدف',
        'category' => 'دسته',
        'code' => 'کد',
        'complexity' => 'وزن',
        'conclusion' => 'نتیجه‌گیری',
        'content' => 'متن',
        'core_action' => 'کنش هسته',
        'core_cost' => 'هزینهٔ هسته',
        'core_reward' => 'پاداش هسته',
        'current_password' => 'گذرواژهٔ کنونی',
        'description' => 'توضیح',
        'design_phase' => 'مرحلهٔ طراحی',
        'display_name' => 'نام',
        'email' => 'نشانی ایمیل',
        'failure_condition' => 'شرط شکست',
        'framework_version_id' => 'ویرایش چارچوب',
        'game_version_id' => 'نسخهٔ بازی',
        'hypothesis' => 'فرضیه',
        'instructions' => 'دستورالعمل',
        'mechanics' => 'مکانیک‌ها',
        'member_id' => 'عضو',
        'name' => 'نام',
        'notes' => 'یادداشت‌ها',
        'objective' => 'هدف',
        'outcome' => 'برآمد',
        'password' => 'گذرواژه',
        'password_confirmation' => 'تأیید گذرواژه',
        'phase_id' => 'مرحله',
        'pitch' => 'معرفی یک‌جمله‌ای',
        'planned_at' => 'تاریخ برنامه‌ریزی‌شده',
        'play_time_max' => 'بیشترین زمان بازی',
        'play_time_min' => 'کمترین زمان بازی',
        'player_count_max' => 'بیشترین تعداد بازیکن',
        'player_count_min' => 'کمترین تعداد بازیکن',
        'position' => 'جایگاه',
        'prompt' => 'پرسش',
        'rating' => 'امتیاز',
        'recovery_code' => 'کد بازیابی',
        'response' => 'پاسخ',
        'role' => 'نقش',
        'slug' => 'نشانی',
        'status' => 'وضعیت',
        'target_age_min' => 'کم‌سن‌ترین بازیکن',
        'title' => 'عنوان',
        'user_id' => 'حساب کاربری',
        'win_condition' => 'شرط برد',
    ],

];
