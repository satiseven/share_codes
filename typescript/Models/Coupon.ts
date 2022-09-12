import { DateTime } from 'luxon'
import { BaseModel, BelongsTo, belongsTo, column } from '@ioc:Adonis/Lucid/Orm'
import User from './User'

export default class Coupon extends BaseModel {
  @column({ isPrimary: true })
  public id: string

  @column()
  public code: string

  @column()
  public userId: string

  @column()
  public percentage: number

  @column()
  public isRepetitive: boolean

  @column()
  public isDeleted: boolean = false

  @column()
  public isUsed: boolean = false

  @column.dateTime()
  public usedAt: DateTime

  @column.dateTime({ autoCreate: true })
  public createdAt: DateTime

  @column.dateTime({ autoCreate: true, autoUpdate: true })
  public updatedAt: DateTime

  @belongsTo(() => User)
  public user: BelongsTo<typeof User>

  public get serializedCouponInfo() {
    const { id, code, userId, percentage, isRepetitive, isUsed, isDeleted } = this

    return {
      id,
      code,
      userId,
      percentage,
      isRepetitive,
      isUsed,
      isDeleted,
    }
  }
}
