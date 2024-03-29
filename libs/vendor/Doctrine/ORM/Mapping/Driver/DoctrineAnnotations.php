<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Mapping;

use Doctrine\Common\Annotations\Annotation;

/* Annotations */

final class Entity extends Annotation {
    public $repositoryClass;
}
final class MappedSuperclass extends Annotation {}
final class InheritanceType extends Annotation {}
final class DiscriminatorColumn extends Annotation {
    public $name;
    public $fieldName; // field name used in non-object hydration (array/scalar)
    public $type;
    public $length;
}
final class DiscriminatorMap extends Annotation {}
/*final class SubClasses extends Annotation {}*/
final class Id extends Annotation {}
final class GeneratedValue extends Annotation {
    public $strategy;
}
final class Version extends Annotation {}
final class JoinColumn extends Annotation {
    public $name;
    public $fieldName; // field name used in non-object hydration (array/scalar)
    public $referencedColumnName;
    public $unique = false;
    public $nullable = true;
    public $onDelete;
    public $onUpdate;
}
final class JoinColumns extends Annotation {}
final class Column extends Annotation {
    public $type;
    public $length;
    public $precision = 0; // The precision for a decimal (exact numeric) column (Applies only for decimal column)
    public $scale = 0; // The scale for a decimal (exact numeric) column (Applies only for decimal column)
    public $unique = false;
    public $nullable = false;
    public $default; //TODO: remove?
    public $name;
    public $options = array();
    public $columnDefinition;
}
final class OneToOne extends Annotation {
    public $targetEntity;
    public $mappedBy;
    public $cascade;
    public $fetch = 'LAZY';
    public $optional;
    public $orphanRemoval = false;
}
final class OneToMany extends Annotation {
    public $mappedBy;
    public $targetEntity;
    public $cascade;
    public $fetch = 'LAZY';
    public $orphanRemoval = false;
}
final class ManyToOne extends Annotation {
    public $targetEntity;
    public $cascade;
    public $fetch = 'LAZY';
    public $optional;
}
final class ManyToMany extends Annotation {
    public $targetEntity;
    public $mappedBy;
    public $cascade;
    public $fetch = 'LAZY';
}
final class ElementCollection extends Annotation {
    public $tableName;
}
final class Table extends Annotation {
    public $name;
    public $schema;
    public $indexes;
    public $uniqueConstraints;
}
final class UniqueConstraint extends Annotation {
    public $name;
    public $columns;
}
final class Index extends Annotation {
    public $name;
    public $columns;
}
final class JoinTable extends Annotation {
    public $name;
    public $schema;
    public $joinColumns;
    public $inverseJoinColumns;
}
final class SequenceGenerator extends Annotation {
    public $sequenceName;
    public $allocationSize = 10;
    public $initialValue = 1;
}
final class ChangeTrackingPolicy extends Annotation {}

/* Annotations for lifecycle callbacks */
final class HasLifecycleCallbacks extends Annotation {}
final class PrePersist extends Annotation {}
final class PostPersist extends Annotation {}
final class PreUpdate extends Annotation {}
final class PostUpdate extends Annotation {}
final class PreRemove extends Annotation {}
final class PostRemove extends Annotation {}
final class PostLoad extends Annotation {}

/* Generic annotation for Doctrine extensions */
final class DoctrineX extends Annotation {}
