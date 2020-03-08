typedef unsigned long sigset_t;

typedef struct _zend_object_handlers zend_object_handlers;
typedef unsigned char zend_uchar;
typedef struct _zend_array zend_array;
typedef struct _zend_object zend_object;
typedef struct _zend_resource zend_resource;
typedef struct _zend_reference zend_reference;
typedef struct _zval_struct zval;
typedef struct _zend_ast_ref    zend_ast_ref;
typedef struct _zend_ast        zend_ast;
typedef struct _zend_class_entry     zend_class_entry;
typedef union  _zend_function        zend_function;
typedef struct _zend_array HashTable;
typedef struct _php_netstream_data_t php_netstream_data_t;
typedef void (*dtor_func_t)(zval *pDest);
typedef unsigned char zend_bool;
typedef struct _zend_op_array zend_op_array;
typedef struct _php_stream php_stream;
typedef struct _zend_ffi_type zend_ffi_type;
typedef uintptr_t zend_uintptr_t;
typedef struct _zend_executor_globals zend_executor_globals;
typedef struct _zend_vm_stack *zend_vm_stack;
typedef struct _zend_execute_data    zend_execute_data;
typedef struct _zend_op zend_op;

typedef struct _zend_refcounted_h {
    uint32_t         refcount;                      /* reference counter 32-bit */
    union {
            uint32_t type_info;
    } u;
} zend_refcounted_h;

typedef struct {
        /* Not using a union here, because there's no good way to initialize them
         * in a way that is supported in both C and C++ (designated initializers
         * are only supported since C++20). */
        void *ptr;
        uint32_t type_mask;
        /* TODO: We could use the extra 32-bit of padding on 64-bit systems. */
} zend_type;
typedef struct _zend_string {
    zend_refcounted_h gc;
    zend_ulong        h;                /* hash value */
    size_t            len;
    char              val[1];
} zend_string;
typedef struct _zend_refcounted {
    zend_refcounted_h gc;
} zend_refcounted;
struct _zend_resource {
    zend_refcounted_h gc;
    int               handle; // TODO: may be removed ???
    int               type;
    void             *ptr;
};
typedef struct _zend_property_info zend_property_info;

typedef union {
        zend_property_info *ptr;
        uintptr_t list;
} zend_property_info_source_list;

struct _zval_struct {
    union {
        zend_long         lval;             /* long value */
        double            dval;             /* double value */
        zend_refcounted  *counted;
        zend_string      *str;
        zend_array       *arr;
        zend_object      *obj;
        zend_resource    *res;
        zend_reference   *ref;
        zend_ast_ref     *ast;
        zval             *zv;
        void             *ptr;
        zend_class_entry *ce;
        zend_function    *func;
        struct {
            uint32_t w1;
            uint32_t w2;
        } ww;
    } value;
    union {
        struct {
                zend_uchar    type;         /* active type */
                zend_uchar    type_flags;
                zend_uchar    const_flags;
                zend_uchar    reserved;     /* call info for EX(This) */
        } v;
        uint32_t type_info;
    } u1;
    union {
        uint32_t     var_flags;
        uint32_t     next;                 /* hash collision chain */
        uint32_t     cache_slot;           /* literal cache slot */
        uint32_t     lineno;               /* line number (for ast nodes) */
        uint32_t     num_args;             /* arguments number for EX(This) */
        uint32_t     fe_pos;               /* foreach position */
        uint32_t     fe_iter_idx;          /* foreach iterator index */
    } u2;
};
struct _zend_ast_ref {
        zend_refcounted_h gc;
        /*zend_ast        ast; zend_ast follows the zend_ast_ref structure */
};
struct _zend_reference {
    zend_refcounted_h              gc;
    zval                           val;
    zend_property_info_source_list sources;
};
struct _zend_object {
    zend_refcounted_h gc;
    uint32_t          handle; // TODO: may be removed ???
    zend_class_entry *ce;
    const zend_object_handlers *handlers;
    HashTable        *properties;
    zval              properties_table[1];
};
typedef struct _Bucket {
    zval              val;
    zend_ulong        h;                /* hash value (or numeric index)   */
    zend_string      *key;              /* string key or NULL for numerics */
} Bucket;

typedef struct _zend_array {
    zend_refcounted_h gc;
    union {
            struct {
                zend_uchar    flags;
                zend_uchar    _unused;
                zend_uchar    nIteratorsCount;
                zend_uchar    _unused2;
            } v;
            uint32_t flags;
    } u;
    uint32_t          nTableMask;
    Bucket           *arData;
    uint32_t          nNumUsed;
    uint32_t          nNumOfElements;
    uint32_t          nTableSize;
    uint32_t          nInternalPointer;
    zend_long         nNextFreeElement;
    dtor_func_t       pDestructor;
};
typedef struct _zend_arg_info {
        zend_string *name;
        zend_type type;
} zend_arg_info;

struct _zend_op_array {
        /* Common elements */
        zend_uchar type;
        zend_uchar arg_flags[3]; /* bitset of arg_info.pass_by_reference */
        uint32_t fn_flags;
        zend_string *function_name;
        zend_class_entry *scope;
        zend_function *prototype;
        uint32_t num_args;
        uint32_t required_num_args;
        zend_arg_info *arg_info;
        /* END of common elements */

        int cache_size;     /* number of run_time_cache_slots * sizeof(void*) */
        int last_var;       /* number of CV variables */
        uint32_t T;         /* number of temporary variables */
        uint32_t last;      /* number of opcodes */

        zend_op *opcodes;
        void*** run_time_cache__ptr;
        //ZEND_MAP_PTR_DEF(void **, run_time_cache);
        HashTable** static_variables_ptr__ptr;
        //ZEND_MAP_PTR_DEF(HashTable *, static_variables_ptr);
        HashTable *static_variables;
        zend_string **vars; /* names of CV variables */

        uint32_t *refcount;

        int last_live_range;
        int last_try_catch;
        void *live_range;
        void *try_catch_array;

        zend_string *filename;
        uint32_t line_start;
        uint32_t line_end;
        zend_string *doc_comment;
        int last_literal;
        zval *literals;

        void *reserved[6];
};
typedef void (*zif_handler)(zend_execute_data *execute_data, zval *return_value);
typedef struct _zend_internal_function {
        /* Common elements */
        zend_uchar type;
        zend_uchar arg_flags[3]; /* bitset of arg_info.pass_by_reference */
        uint32_t fn_flags;
        zend_string* function_name;
        zend_class_entry *scope;
        zend_function *prototype;
        uint32_t num_args;
        uint32_t required_num_args;
        void *arg_info;
        /* END of common elements */

        zif_handler handler;
        void *module;
        void *reserved[6];
} zend_internal_function;

union _zend_function {
        zend_uchar type;        /* MUST be the first element of this struct! */
        uint32_t   quick_arg_flags;

        struct {
                zend_uchar type;  /* never used */
                zend_uchar arg_flags[3]; /* bitset of arg_info.pass_by_reference */
                uint32_t fn_flags;
                zend_string *function_name;
                zend_class_entry *scope;
                zend_function *prototype;
                uint32_t num_args;
                uint32_t required_num_args;
                zend_arg_info *arg_info;  /* index -1 represents the return value info, if any */
        } common;

        zend_op_array op_array;
        zend_internal_function internal_function;
};

typedef enum _zend_ffi_symbol_kind {
	ZEND_FFI_SYM_TYPE,
	ZEND_FFI_SYM_CONST,
	ZEND_FFI_SYM_VAR,
	ZEND_FFI_SYM_FUNC
} zend_ffi_symbol_kind;

struct _zend_ffi_type {
	int     kind;
	size_t                 size;
	uint32_t               align;
	uint32_t               attr;
	union {
		struct {
			zend_string        *tag_name;
			int  kind;
		} enumeration;
		struct {
			zend_ffi_type *type;
			zend_long      length;
		} array;
		struct {
			zend_ffi_type *type;
		} pointer;
		struct {
			zend_string   *tag_name;
			HashTable      fields;
		} record;
		struct {
			zend_ffi_type *ret_type;
			HashTable     *args;
			int        abi;
		} func;
	};
};

typedef struct _zend_ffi_symbol {
	zend_ffi_symbol_kind   kind;
	zend_bool              is_const;
	zend_ffi_type         *type;
	union {
		void *addr;
		int64_t value;
	};
} zend_ffi_symbol;

typedef struct _zend_ffi {
	zend_object            std;
	void*              lib;
	HashTable             *symbols;
	HashTable             *tags;
	zend_bool              persistent;
} zend_ffi;

typedef struct _zend_ffi_cdata {
	zend_object            std;
	zend_ffi_type         *type;
	void                  *ptr;
	void                  *ptr_holder;
	int         flags;
} zend_ffi_cdata;

typedef union _znode_op {
        uint32_t      constant;
        uint32_t      var;
        uint32_t      num;
        uint32_t      opline_num; /*  Needs to be signed */
        #IF_ZEND_USE_ABS_MACRO#
} znode_op;

struct _zend_op {
        const void *handler;
        znode_op op1; 
        znode_op op2; 
        znode_op result;
        uint32_t extended_value;
        uint32_t lineno;
        zend_uchar opcode;
        zend_uchar op1_type;
        zend_uchar op2_type;
        zend_uchar result_type;
};

struct _zend_vm_stack {
        zval *top;
        zval *end;
        zend_vm_stack prev;
};
typedef struct _zend_stack {
        int size, top, max;
        void *elements;
} zend_stack;

struct _zend_execute_data {
        const zend_op       *opline;           /* executed opline                */
        zend_execute_data   *call;             /* current call                   */
        zval                *return_value;
        zend_function       *func;             /* executed function              */
        zval                 This;             /* this + call_info + num_args    */
        zend_execute_data   *prev_execute_data;
        zend_array          *symbol_table;
        void               **run_time_cache;   /* cache op_array->run_time_cache */
};

typedef enum {
        EH_NORMAL = 0,
        EH_THROW
} zend_error_handling_t;

typedef struct _zend_objects_store {
        zend_object **object_buckets;
        uint32_t top;
        uint32_t size;
        int free_list_head;
} zend_objects_store;
typedef uint32_t HashPosition;

typedef struct _HashTableIterator {
        HashTable    *ht; 
        HashPosition  pos; 
} HashTableIterator;

struct _zend_executor_globals {
        zval uninitialized_zval;
        zval error_zval;

        /* symbol table cache */
        zend_array *symtable_cache[32];
        /* Pointer to one past the end of the symtable_cache */
        zend_array **symtable_cache_limit;
        /* Pointer to first unused symtable_cache slot */
        zend_array **symtable_cache_ptr;

        zend_array symbol_table;                /* main symbol table */

        HashTable included_files;       /* files already included */

        void *bailout;

        int error_reporting;
        int exit_status;

        HashTable *function_table;      /* function symbol table */
        HashTable *class_table;         /* class table */
        HashTable *zend_constants;      /* constants table */

        zval          *vm_stack_top;
        zval          *vm_stack_end;
        zend_vm_stack  vm_stack;
        size_t         vm_stack_page_size;

        struct _zend_execute_data *current_execute_data;
        zend_class_entry *fake_scope; /* used to avoid checks accessing properties */

        zend_long precision;

        int ticks_count;

        uint32_t persistent_constants_count;
        uint32_t persistent_functions_count;
        uint32_t persistent_classes_count;

        HashTable *in_autoload;
        zend_function *autoload_func;
        zend_bool full_tables_cleanup;

        /* for extended information support */
        zend_bool no_extensions;

        zend_bool vm_interrupt;
        zend_bool timed_out;
        zend_long hard_timeout;

        #IF_ZEND_WIN32_MACRO#

        HashTable regular_list;
        HashTable persistent_list;

        int user_error_handler_error_reporting;
        zval user_error_handler;
        zval user_exception_handler;
        zend_stack user_error_handlers_error_reporting;
        zend_stack user_error_handlers;
        zend_stack user_exception_handlers;

        zend_error_handling_t  error_handling;
        zend_class_entry      *exception_class;
        /* timeout support */
        zend_long timeout_seconds;

        int lambda_count;

        HashTable *ini_directives;
        HashTable *modified_ini_directives;
        void *error_reporting_ini_entry;

        zend_objects_store objects_store;
        zend_object *exception, *prev_exception;
        const zend_op *opline_before_exception;
        zend_op exception_op[3];

        void *current_module;

        zend_bool active;
        zend_uchar flags;

        zend_long assertions;

        uint32_t           ht_iterators_count;     /* number of allocatd slots */
        uint32_t           ht_iterators_used;      /* number of used slots */
        HashTableIterator *ht_iterators;
        HashTableIterator  ht_iterators_slots[16];

        void *saved_fpu_cw_ptr;
        #IF_XPFPA_HAVE_CW_MACRO#

        zend_function trampoline;
        zend_op       call_trampoline_op;

        HashTable weakrefs;

        zend_bool exception_ignore_args;

        void *reserved[6];
};

typedef union _mm_align_test {
  void *ptr;
  double dbl;
  long lng;
} mm_align_test;

//extern zend_executor_globals executor_globals;
extern zend_array *zend_rebuild_symbol_table(void);
//extern HashTable*  zend_array_dup(HashTable *source);
//zval* zend_hash_find(const HashTable *ht, zend_string *key);
