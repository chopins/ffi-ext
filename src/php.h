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


typedef enum _zend_ffi_symbol_kind {
	ZEND_FFI_SYM_TYPE,
	ZEND_FFI_SYM_CONST,
	ZEND_FFI_SYM_VAR,
	ZEND_FFI_SYM_FUNC
} zend_ffi_symbol_kind;

typedef enum _zend_ffi_type_kind {
        ZEND_FFI_TYPE_VOID,
        ZEND_FFI_TYPE_FLOAT,
        ZEND_FFI_TYPE_DOUBLE,
        HAVE_LONG_DOUBLE_ZEND_FFI_TYPE_LONGDOUBLE
        ZEND_FFI_TYPE_UINT8,
        ZEND_FFI_TYPE_SINT8,
        ZEND_FFI_TYPE_UINT16,
        ZEND_FFI_TYPE_SINT16,
        ZEND_FFI_TYPE_UINT32,
        ZEND_FFI_TYPE_SINT32,
        ZEND_FFI_TYPE_UINT64,
        ZEND_FFI_TYPE_SINT64,
        ZEND_FFI_TYPE_ENUM,
        ZEND_FFI_TYPE_BOOL,
        ZEND_FFI_TYPE_CHAR,
        ZEND_FFI_TYPE_POINTER,
        ZEND_FFI_TYPE_FUNC,
        ZEND_FFI_TYPE_ARRAY,
        ZEND_FFI_TYPE_STRUCT,
} zend_ffi_type_kind;


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

extern zend_array *zend_rebuild_symbol_table(void);
ZEND_FASTCALL HashTable*  zend_array_dup(HashTable *source);
ZEND_FASTCALL zval* zend_hash_find(const HashTable *ht, zend_string *key);
