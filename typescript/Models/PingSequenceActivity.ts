import { DateTime } from 'luxon'
import { BaseModel, BelongsTo, belongsTo, column } from '@ioc:Adonis/Lucid/Orm'
import User from './User'
import PingSequence from './PingSequence'

export default class PingSequenceActivity extends BaseModel {
  @column({ isPrimary: true })
  public id: string

  @column()
  public status: string

  @column()
  public isDeleted: boolean = false

  @column()
  public pingSequenceId: string

  @column()
  public userId: string

  @column.dateTime({ autoCreate: true })
  public createdAt: DateTime

  @column.dateTime({ autoCreate: true, autoUpdate: true })
  public updatedAt: DateTime

  @belongsTo(() => User)
  public user: BelongsTo<typeof User>

  @belongsTo(() => PingSequence)
  public pingSequence: BelongsTo<typeof PingSequence>

  public get serializedPingSequenceActivityInfo() {
    const { id, status, userId, pingSequenceId, isDeleted } = this

    return {
      id,
      status,
      userId,
      pingSequenceId,
      isDeleted,
    }
  }
}
