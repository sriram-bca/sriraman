# train.py - transfer learning skeleton for plant disease (example)
# Usage (after filling dataset paths):
# python train.py --data_dir ./data --epochs 10 --save model.h5

import argparse
import tensorflow as tf
from tensorflow.keras.preprocessing.image import ImageDataGenerator
from tensorflow.keras.applications import MobileNetV2
from tensorflow.keras import layers, models, optimizers

parser = argparse.ArgumentParser()
parser.add_argument('--data_dir', required=True)
parser.add_argument('--epochs', type=int, default=10)
parser.add_argument('--save', default='model.h5')
args = parser.parse_args()

IMG_SIZE = (224,224)
batch_size = 16

train_datagen = ImageDataGenerator(rescale=1./255, validation_split=0.2,
                                   rotation_range=20, width_shift_range=0.2,
                                   height_shift_range=0.2, shear_range=0.15,
                                   zoom_range=0.2, horizontal_flip=True)

train_gen = train_datagen.flow_from_directory(args.data_dir, target_size=IMG_SIZE, batch_size=batch_size, subset='training')
val_gen = train_datagen.flow_from_directory(args.data_dir, target_size=IMG_SIZE, batch_size=batch_size, subset='validation')

base = MobileNetV2(weights='imagenet', include_top=False, input_shape=IMG_SIZE + (3,))
base.trainable = False

model = models.Sequential([
    base,
    layers.GlobalAveragePooling2D(),
    layers.Dense(128, activation='relu'),
    layers.Dropout(0.3),
    layers.Dense(train_gen.num_classes, activation='softmax')
])

model.compile(optimizer=optimizers.Adam(1e-4), loss='categorical_crossentropy', metrics=['accuracy'])
model.fit(train_gen, epochs=args.epochs, validation_data=val_gen)
model.save(args.save)
print('Saved model to', args.save)